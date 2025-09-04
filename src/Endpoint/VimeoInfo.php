<?php

namespace Jk\Vts\Endpoint;

use Jk\Vts\Services\VimeoInfoVideoList;

/** @package Jk\Vts\Endpoint */
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

        $videos = VimeoInfoVideoList::getVideoInfoList($videos);

        return rest_ensure_response($videos);
    }
}
