<?php

namespace Jk\Vts\Endpoint;

class PagesWithVideo {
    protected string $namespace = 'vts/v1';
    protected string $route = 'pages-with-video';

    public function __construct() {
    }

    public function register() {
        register_rest_route(
            $this->namespace,
            $this->route,
            [
                'methods' => 'GET',
                'callback' => [$this, 'get'],
                'permission_callback' => [$this, 'permission'],
                'args' => [
                    'videoId' => [
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
            ]
        );
    }

    public function permission() {
        return current_user_can('read');
    }

    public function get(\WP_REST_Request $request) {
        $videoId = $request->get_param('videoId');
        if (empty($videoId)) {
            return rest_ensure_response(new \WP_Error('invalid_video', 'Video is required', array('status' => 400)));
        }

        $results = collect(get_posts([
            'post_type' => ['page', 'post', 'sfwd-lessons'],
            'posts_per_page' => -1,
        ]))
            ->filter(function ($post) use ($videoId) {
                return str_contains($post->post_content, "vimeo.com")
                    && str_contains($post->post_content, $videoId);
            })
            ->map(function ($post) {
                return get_permalink($post) ?? "";
            })->all();

        return rest_ensure_response(['records' => array_values($results)]);
    }
}
