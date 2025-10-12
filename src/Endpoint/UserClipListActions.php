<?php

namespace Jk\Vts\Endpoint;

use Jk\Vts\Services\AimClipList\ClipListMeta;
use Jk\Vts\Services\AimClipList\AimClipListUserMeta;

class UserClipListActions {
    protected string $namespace = 'vts/v1';
    protected string $baseRoute = 'learning-path';

    public array $routes;
    private ClipListMeta $meta;
    private AimClipListUserMeta $userMeta;

    public function __construct() {
        $this->userMeta = new AimClipListUserMeta();
        $this->meta = new ClipListMeta();
        $this->routes = $this->getRoutes();
    }

    public function optOutUser(\WP_REST_Request $request) {
        $clipListId = $request->get_param('cliplist_id');
        if (!$clipListId) {
            return new \WP_Error('invalid_params', 'clip list id are required', array('status' => 400));
        }

        $user = null;

        if (!is_user_logged_in()) {
            $userEmail = $request->get_param('email');
            if (!$userEmail) {
                return new \WP_Error('invalid_params', 'User email and clip list id are required', array('status' => 400));
            }
            $user = get_user_by('email', $userEmail);
        } else {
            $user = wp_get_current_user();
        }
        $this->userMeta->deleteNextEmailForList($user->ID, (int)$clipListId);
        $this->userMeta->removeSubscribedList($user->ID, (int)$clipListId);

        return rest_ensure_response(['status'=>'success']);
    }

    public function getRoutes() {
        return [
            $this->makeRoutePath('opt-out-user') => [
                'methods' => 'POST',
                'callback' => [$this, 'optOutUser'],
                'args' => [
                    'email' => [
                        'type' => 'string',
                    ],
                    'cliplist_id' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                ],
            ],
        ];
    }

    private function makeRoutePath($slug) {
        return $this->baseRoute . '/' . $slug;
    }
    public function register() {
        foreach ($this->routes as $route => $config) {
            $config['permission_callback'] = function () {
                $user = wp_get_current_user();
                return $user->ID > 0;
            };
            register_rest_route(
                $this->namespace,
                $route,
                $config
            );
        }
    }
}
