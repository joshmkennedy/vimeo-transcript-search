<?php

namespace Jk\Vts\Endpoint;

use Jk\Vts\Services\AimClipList\ClipListCSVParser;
use \Jk\Vts\Services\AimClipList\ClipListMeta;
use Jk\Vts\Services\AiSummarize;
use Jk\Vts\Services\Cache;
use Jk\Vts\Services\Email\PreviewClipListEmail;
use Jk\Vts\Services\Logging\LoggerTrait;
use Jk\Vts\Services\TranscriptChunk;

class ACLEditor {
    use LoggerTrait;
    protected string $namespace = 'vts/v1';
    protected string $baseRoute = 'aim-clip-list-editor';
    public array $routes;
    public ClipListMeta $meta;
    public ClipListCSVParser $clipListCsvParser;
    public function __construct() {
        $this->meta = new ClipListMeta();
        $this->routes = $this->getRoutes();
        $this->clipListCsvParser = new ClipListCSVParser();
    }


    //** LIST META **//

    public function getList(\WP_REST_Request $request) {
        $postId = $request->get_param('post_id');
        if (!$postId) {
            return rest_ensure_response(new \WP_Error('invalid_post_id', 'Post ID is not valid', array('status' => 400)));
        }

        $data = $this->meta->getItems($postId);
        if (is_wp_error($data)) {
            return rest_ensure_response(new \WP_Error('failed_to_get_meta', $data->get_error_message(), array('status' => 400)));
        }

        return rest_ensure_response($data);
    }

    public function uploadCsv(\WP_REST_Request $request) {
        $postId = $request->get_param('post_id');
        if (!$postId) {
            $this->log()->info("creating post...");
            $postId = wp_insert_post([
                'post_title' => "New Aim Clip List: " . date('Y-m-d H:i:s'),
                'post_type' => 'aim-clip-list',
                'post_status' => 'publish',
            ]);
        }
        $this->log()->info("post id: $postId");
        if (is_wp_error($postId)) {
            return rest_ensure_response($postId);
        }
        if (!isset($_FILES['file']['tmp_name']) || !$_FILES['file']['tmp_name']) {
            return rest_ensure_response(new \WP_Error('invalid_file', 'File is missing', array('status' => 400)));
        }
        $file = $_FILES['file']['tmp_name'];

        $items = $this->clipListCsvParser->parse($file);
        if (is_wp_error($items)) {
            return rest_ensure_response($items);
        }
        $resources = $this->meta->getResources($postId);
        if (!$resources || !is_array($resources)) {
            $resources = [];
        }
        $weeksInfo = $this->meta->getWeeksInfo($postId);

        $formId = $this->meta->getFormId($postId);

        $category = get_the_terms($postId, 'aim-clip-list-category');
        if ($category && is_array($category) && count($category) > 0) {
            $category = $category[0]->term_id;
        } else {
            $category = get_term_by('slug', 'beginner', 'aim-clip-list-category')?->term_id ?? 74;
        }

        $this->log()->info("saving meta");
        $error = $this->meta->save($postId, $items, $resources, $weeksInfo, $formId ?? 21185);
        if (is_wp_error($error)) {
            return rest_ensure_response($error);
        }

        $this->log()->info("saving category");
        $res = wp_set_post_terms($postId, [$category], 'aim-clip-list-category', false);
        if (is_wp_error($res)) {
            return rest_ensure_response($res);
        }

        $this->log()->info("sending data");
        return rest_ensure_response([
            'postId' => $postId,
            'post' => [
                'title' => get_the_title($postId)
            ],
            'items' => $items,
            'resources' => $resources,
            'weeksInfo' => $this->meta->getWeeksForEditor($postId),

            'formId' => $this->meta->getFormId($postId),
            'category' => $category,
        ]);
    }

    public function buildResources(\WP_REST_Request $request) {
        $body = json_decode($request->get_body(), true);
        if (!isset($body['weeks'])) {
            error_log("body is empty");
            return new \WP_Error('invalid_body', 'Request body is missing or invalid.', ['status' => 400]);
        }
        $weeks = $body['weeks'];
        $resources = [];

        $videoPages = collect(get_posts([
            'post_type' => ['sfwd-lessons'],
            'posts_per_page' => -1,
        ]));


        foreach ($weeks as $week => $videos) {
            $pages = $videoPages->filter(function ($post) use ($videos) {
                return collect($videos)->contains(function ($video) use ($post) {
                    return str_contains($post->post_content, "vimeo.com")
                        && str_contains($post->post_content, $video['vimeoId']);
                });
            })->map(function ($post) {
                return $post->ID;
            })->unique(fn($id) => $id);
            $this->log()->info("pages with vimeoId: " . count($pages));

            $pages->each(function ($post) use ($week, &$resources) {
                $post = get_post($post);
                if (!$post) {
                    return;
                }
                $content = $post->post_content;

                preg_match_all('/href=["\']([^"\']*\/aim\/resource[^"\']*)["\']/i', $content, $matches);
                if (empty($matches[1])) {
                    return;
                }
                foreach ($matches[1] as $match) {
                    $resource = get_post(url_to_postid($match));
                    if (!$resource) {
                        continue;
                    }
                    $resource = [
                        'label' => get_the_title($resource),
                        'link' => $match,
                        'week_index' => $week,
                    ];
                    $resources[] = $resource;
                    error_log(print_r($resources, true));
                }
            });
        }

        return rest_ensure_response(['resources' => $resources]);
    }

    public function save(\WP_REST_Request $request) {
        $body = json_decode($request->get_body(), true);

        if (!isset($body['postId']) || !isset($body['items']) || !isset($body['post'])) {
            error_log("body is empty");
            return new \WP_Error('invalid_body', 'Request body is missing or invalid.', ['status' => 400]);
        }
        $postId = (int)$body['postId'];
        $items = $body['items'];
        $post = $body['post'];
        $resources = $body['resources'] ?? [];
        $formId = $body['formId'] ?? 21185;

        $categoryId = $body['category'] ?? 74;
        $category = get_term((int)$categoryId, 'aim-clip-list-category');
        if (!$category) {
            new \WP_Error('invalid_category', 'Category not found', ['status' => 400]);
        }

        $weeksInfo = [];
        try {
            $weeksInfo = $this->meta->normalizeWeeksFromEditor(
                $body['weeksInfo'] ?? $this->meta->getWeeksForEditor($postId),
            );
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new \WP_Error('invalid_weeks', $e->getMessage(), ['status' => 400]);
        }


        $this->log()->info("updating post");
        $error = wp_update_post([
            'ID' => $postId,
            'post_title' => $post['title'],
        ], true);

        if (is_wp_error($error)) {
            return rest_ensure_response($error);
        }
        $this->log()->info("saving meta");
        $this->meta->save($postId, $items, $resources, $weeksInfo, $formId);

        $this->log()->info("saving category");
        $res = wp_set_post_terms($postId, [$category->term_id], 'aim-clip-list-category', false);
        if (is_wp_error($res)) {
            return rest_ensure_response($res);
        }

        $this->log()->info("sending data");
        try {
            return rest_ensure_response([
                'postId' => $postId,
                'post' => [
                    'title' => get_the_title($postId)
                ],
                'items' => $this->meta->getItems($postId),
                'resources' => $this->meta->getResources($postId) ?? [],
                'weeksInfo' => $this->meta->getWeeksInfo($postId) ?? [],
                'formId' => $this->meta->getFormId($postId),
                'category' => $category->term_id,

            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return rest_ensure_response(new \WP_Error('failed_to_responed', 'Couldnt get fresh data to resond with', array('status' => 400)));
        }
    }

    public function getNewClipId(\WP_REST_Request $request) {
        $clipId = $this->meta->createId();
        return rest_ensure_response($clipId);
    }

    public function summarizeVideo(\WP_REST_Request $request) {
        $body = json_decode($request->get_body(), true);
        if (!isset($body['vimeoId']) || !isset($body['start']) || !isset($body['end']) || !isset($body['clipId'])) {
            return new \WP_Error('invalid_body', 'Request body is missing or invalid, missing videos.', ['status' => 400]);
        }

        $aiSummarize = new AiSummarize();
        $cache = new Cache(
            fn($info) => "ai-video-summary-{$info['clipId']}",
            fn($info) => $aiSummarize->summarizeVideo($info['content'], implode("\n- ", $info['keypoints'])),
            10 * MINUTE_IN_SECONDS,
            isset($body['bypassCache']) ? $body['bypassCache'] : false,
        );

        $this->log()->info("getting the contents from the db for {$body['vimeoId']}:{$body['start']}-{$body['end']}...");

        try {
            $tc = (new TranscriptChunk())->get($body['vimeoId'], (int)$body['start'], (int)$body['end']);
            $info = [
                'content' => $tc->concatOn('summary'),
                'keypoints' => $tc->keyPoints(),
                'clipId' => $body['clipId'],
            ];
            $this->log()->info("getting summary for {$body['clipId']}...");
            $info['summary'] = $cache->get($info);
            $this->log()->info("got summary for {$body['clipId']}");
            $results = [
                'clipId' => $body['clipId'],
                'summary' => $info['summary'],
            ];
            return rest_ensure_response($results);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new \WP_Error('failed_to_summarize', "Failed to summarize. {$e->getMessage()}", ['status' => 400]);
        }
    }

    public function summarizeEmail(\WP_REST_Request $request) {
        $body = json_decode($request->get_body(), true);

        if (!isset($body['weekIndex']) || !isset($body['summaries']) || !is_array($body['summaries']) || !isset($body['postId'])) {
            return new \WP_Error('invalid_body', 'Request body is missing or is invalid.', ['status' => 400]);
        }

        if (!isset($body['bypassCache'])) {
            $this->log()->info("summarizing email, and bypassCache is set", [$body['bypassCache']]);
        }

        $aiSummarize = new AiSummarize();
        $cache = new Cache(
            fn($info) => "ai-week-email-summary-{$info['weekIndex']}-{$info['postId']}",
            fn($info) => $aiSummarize->summarizeEmail(implode("\n-", $info['summaries'])),
            10 * MINUTE_IN_SECONDS,
            isset($body['bypassCache']) ? $body['bypassCache'] : false,
        );

        try {
            $summary = $cache->get($body);
            $results = [
                'weekIndex' => $body['weekIndex'],
                'summary' => $summary,
            ];
            return rest_ensure_response($results);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return new \WP_Error('failed_to_summarize', "Failed to summarize. {$e->getMessage()}", ['status' => 400]);
        }
    }

    function generateEmailPreview(\WP_REST_Request $request) {
        $body = json_decode($request->get_body(), true);
        if(!isset($body['postId']) || !isset($body['content'])) {
            return new \WP_Error('invalid_body', 'Request body is missing or invalid.', ['status' => 400]);
        }

        if(!isset($body['videos'])){
            return new \WP_Error('invalid_body', 'Request body is missing videos or invalid.', ['status' => 400]);
        }
        $videos = $body['videos'];
        if(!is_array($videos) || !collect($videos)->every(fn($video)=> isset($video['image_url']) && isset($video['summary']) && isset($video['video_type']) && isset($video['title']) && isset($video['vimeoId']))){
            return new \WP_Error('invalid_body', 'Request body is missing videos are missing properties needed for the email tempalte preview.', ['status' => 400]);
        }

        if(!isset($body['resources'])){
            return new \WP_Error('invalid_body', 'Request body is missing resources or invalid.', ['status' => 400]);
        }
        $resources = $body['resources'];
        if(!is_array($resources) || !collect($resources)->every(fn($resource)=> isset($resource['link']) && isset($resource['label']))){
            return new \WP_Error('invalid_body', 'Request body\'s resources are missing properties needed for the email tempalte preview.', ['status' => 400]);
        }

        $email = new PreviewClipListEmail([
            'title'=> "This weeks videos and resources",
            'videos' => $videos,
            'resources' => $resources,
            'content'=> $body['content'],
            'clipListId'=> $body['postId'],
            'week_index'=> $body['week_index'],
        ]);
        $user = wp_get_current_user();
        return rest_ensure_response($email->generateClipListEmail($user->user_email));
    }

    // ** CONFIG FOR ROUTES **//
    public function getRoutes() {
        return [
            $this->makeRoutePath('list') => [
                'methods' => 'GET',
                'callback' => [$this, 'getList'],
                'args' => [
                    'post_id' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                ],
            ],
            $this->makeRoutePath('upload-csv') => [
                'methods' => 'POST',
                'callback' => [$this, 'uploadCsv'],
                'args' => [
                    'post_id' => [
                        'type' => 'integer',
                    ],
                ],
            ],

            $this->makeRoutePath('save') => [
                'methods' => 'POST',
                'callback' => [$this, 'save'],
            ],

            $this->makeRoutePath('get-new-clip-id') => [
                'methods' => 'POST',
                'callback' => [$this, 'getNewClipId'],
            ],

            $this->makeRoutePath('build-resources') => [
                'methods' => 'POST',
                'callback' => [$this, 'buildResources'],
            ],

            $this->makeRoutePath('summarize-video') => [
                'methods' => 'POST',
                'callback' => [$this, 'summarizeVideo'],
            ],
            $this->makeRoutePath('summarize-email') => [
                'methods' => 'POST',
                'callback' => [$this, 'summarizeEmail'],
            ],

            $this->makeRoutePath('email-preview') => [
                'methods' => 'POST',
                'callback' => [$this, 'generateEmailPreview'],
            ],
        ];
    }

    private function makeRoutePath($slug) {
        return $this->baseRoute . '/' . $slug;
    }

    public function register() {
        foreach ($this->routes as $route => $config) {
            $config['permission_callback'] = function () {
                return current_user_can('edit_posts');
                // return true;
            };
            register_rest_route(
                $this->namespace,
                $route,
                $config
            );
        }
    }
}
