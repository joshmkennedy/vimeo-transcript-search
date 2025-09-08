<?php

namespace Jk\Vts\Admin;

use Jk\Vts\Services\AimClipList\ClipListMeta;
use Jk\Vts\Services\Logging\LoggerTrait;
use Jk\Vts\Services\VimeoInfoVideoList;
use Jk\Vts\Services\Vite;

class ACLAdminPage {
    use LoggerTrait;

    protected \Jk\Vts\Services\Vite $assets;
    const NONCE = "vts-admin-none";
    const SLUG = "aim-clip-list";

    private ClipListMeta $meta;
    public function __construct(public string $pluginPath, public string $pluginUrl) {
        $this->assets = new Vite(plugin_path: $this->pluginPath, plugin_url: $this->pluginUrl);
        $this->meta = new ClipListMeta();
    }


    public function registerPostType() {
        $config = require_once $this->pluginPath . '/src/Config/aim-clip-list-post-type.php';
        register_post_type(self::SLUG, $config['args']);
        register_meta('post', $this->meta::metaKey, [
            'type' => 'array',
            'show_in_rest' => [
                'schema' => $this->meta->getItemsSchema(),
            ],
            'single' => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
        register_meta('post', $this->meta::resourcesKey, [
            'type' => 'array',
            'show_in_rest' => [
                'schema' => $this->meta->getResourceSchema(),
            ],
            'single' => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
        register_meta('post', $this->meta::weeksInfoKey, [
            'type' => 'array',
            'show_in_rest' => [
                'schema' => $this->meta->getWeeksInfoSchema(),
            ],
            'single' => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);
    }

    public function enqueueAsset(): void {
        global $post, $current_screen;
        if ($current_screen->base != "admin_page_edit-" . self::SLUG) return;
        $id = isset($post) && isset($post->ID) ? $post->ID : (
            isset($_GET['post_id']) ? $_GET['post_id'] : 0
        );
        $handle = $this->assets->use('assets/src/aim-clip-list-editor.ts');
        $list = $this->meta->getItems($id);
        $resources = $this->meta->getResources($id);
        wp_localize_script($handle, 'vtsACLEditor', [
            'nonce' => wp_create_nonce('wp_rest'),
            'apiUrl' => get_rest_url(path: '/vts/v1/aim-clip-list-editor'),
            'postId' =>  $id,
            'items' => $list,
            'post' => [
                'title' => $id ? get_the_title($id) : '',
            ],
            'previewList' => is_array($list) ? VimeoInfoVideoList::getVideoInfoSet($list) : [],
            'resources' => $resources,
            'weeksInfo' => $this->meta->getWeeksForEditor($id),
            // add taxonomy terms
            // add 
        ]);
    }

    public function postTypeEditorLink($link, $post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type == self::SLUG) {
            $link = admin_url('admin.php?page=edit-' . self::SLUG . '&post_id=' . $post_id);
        }
        return $link;
    }

    public function redirectNewPost($current_screen): void {
        if ($current_screen->base == "post" && $current_screen->action == "add" && $current_screen->post_type == self::SLUG) {
            wp_redirect(admin_url("admin.php?page=edit-" . self::SLUG . "&new=1"));
            exit;
        }
    }

    public function registerEditorPage() {
        add_submenu_page(
            null, // No parent menu, so it's hidden
            'Edit Aim Clip List',
            'Edit Aim Clip List',
            'manage_options',
            "edit-" . self::SLUG,
            [$this, 'renderPostEditor']
        );
    }

    public function renderPostEditor() {
        echo <<<HTML
        <div class="wrap">
            <div id="aim-clip-list-editor-app"></div>
        </div>
HTML;
    }
}
