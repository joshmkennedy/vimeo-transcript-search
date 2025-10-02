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
        $categoryConfig = require_once $this->pluginPath . '/src/Config/aim-clip-list-category.php';
        register_taxonomy(
            $categoryConfig['slug'],
            self::SLUG,
            $categoryConfig['args']
        );

        register_meta('post', $this->meta::formId, [
            'type' => 'number',
            'show_in_rest' => true,
            'single' => true,
            'auth_callback' => function () {
                return current_user_can('edit_posts');
            },
        ]);

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


        $list = $this->meta->getItems($id);
        $resources = $this->meta->getResources($id);
        $category = get_the_terms($id, 'aim-clip-list-category');
        if ($category && is_array($category) && count($category) > 0) {
            $category = $category[0]->term_id;
        } else {
            $category = get_term_by('slug', 'beginner', 'aim-clip-list-category')?->term_id ?? 74;
        }

        $handle = $this->assets->use('assets/src/aim-clip-list-editor.ts');
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
            'formId' => $this->meta->getFormId($id),
            'category' => $category,
            'clipListCategories' => get_terms(['taxonomy' => 'aim-clip-list-category', 'hide_empty' => false, 'fields' => 'id=>name']),
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
        echo "
        <div class='wrap'>
            <div id='aim-clip-list-editor-app'></div>
        </div>
";
    }
}
