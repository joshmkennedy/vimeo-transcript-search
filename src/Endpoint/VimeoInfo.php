<?php

namespace Jk\Vts\Endpoint;

class VimeoInfo {

    protected string $namespace = 'vts/v1';
    protected string $route = 'vid-info';
    protected string $vimeoApiKey;

    public function __construct() {
        $this->vimeoApiKey = get_option('vimeo-api-key');
    }
    public function register() {
        register_rest_route(
            $this->namespace,
            $this->route,
            [
                'methods' => 'POST',
                'callback' => [$this, 'post'],
                'permission_callback' => [$this, 'permission'],
                // 'args' => [
                //     'videos' => [
                //         'type' => 'array',
                //         'required' => true,
                //     ],
                // ],
            ]
        );
    }

    public function permission() {
        return current_user_can('read');
    }

    public function post(\WP_REST_Request $request) {
        $body = json_decode($request->get_body(), true);
        $videos = [];
        if (!$body) {
            return rest_ensure_response(new \WP_Error('invalid_body', 'Request body is missing or invalid.', array('status' => 400)));
        }
        if (!empty($body['videos'])) {
            $videos = $body['videos'];
        } else {
            return rest_ensure_response(new \WP_Error('invalid_videos', 'Videos is required', array('status' => 400)));
        }
        $vimeoApi = new \Jk\Vts\Services\VimeoApi();
        foreach ($videos as $key => $video) {
            $vimeoId = $video['vimeoId'] ?? null;
            if ($vimeoId) {
                if($cached = get_transient("vts_vimeo_info_$vimeoId")){
                    $videos[$key] = array_merge($cached, $video);
                    continue;
                }
                $videos[$key] = array_merge($video, $vimeoApi->getVideoInfo($vimeoId, ['name', 'uri', 'pictures' => [0 => 'base_link'], 'player_embed_url']));
                set_transient("vts_vimeo_info_$vimeoId", $videos[$key], \YEAR_IN_SECONDS);
            }
        }

        return rest_ensure_response($videos);
    }
}
