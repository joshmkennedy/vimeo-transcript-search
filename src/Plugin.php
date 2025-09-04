<?php

namespace Jk\Vts;


use Jk\Vts\Services\Vite;

/** @package Jk\Vts */
class Plugin {
    public string $basename;
    public string $dir;
    public string $path;
    public string $url;
    public string $version;
    private bool $loaded;
    public function __construct(
        public string $file,
    ) {
        $this->loaded = false;
        $this->basename = plugin_basename($this->file);
        $this->dir = dirname($this->file);
        $this->path = plugin_dir_path($this->file);
        $this->url = plugin_dir_url($this->file);
        $this->version = "0.0.1";
    }


    public function run() {
        if ($this->loaded) {
            return;
        }

        //ENSURE EMBED DB

        $this->addHooks();
        $this->loaded = true;
    }

    private function addHooks() {
        // POST TYPES
        add_action(
            'init',
            [
                new \Jk\Vts\Admin\ACLAdminPage($this->path, $this->url),
                'registerPostType'
            ]
        );

        // REST ROUTES
        add_action(
            'rest_api_init',
            [
                new \Jk\Vts\Endpoint\UploadTranscript(),
                'register'
            ]
        );
        add_action(
            'rest_api_init',
            [
                new \Jk\Vts\Endpoint\ListTranscribedVideos(),
                'register'
            ]
        );
        add_action(
            'rest_api_init',
            [
                new \Jk\Vts\Endpoint\SearchTranscriptionEmbeds(),
                'register'
            ]
        );
        add_action('rest_api_init', [
            new \Jk\Vts\Endpoint\PagesWithVideo(),
            'register'
        ]);
        add_action('rest_api_init', [
            new \Jk\Vts\Endpoint\VimeoInfo(),
            'register'
        ]);
        // AIM CLIP LIST Editor Endpoints
        add_action(
            'rest_api_init',
            [
                new \Jk\Vts\Endpoint\ACLEditor(),
                'register'
            ]
        );

        // ADMIN
        add_action(
            'admin_menu',
            [
                new \Jk\Vts\Admin\AdminPage($this->path, $this->url),
                'register'
            ]
        );



        // AIM CLIP LIST Post Type Editor
        add_action(
            'admin_menu',
            [
                new \Jk\Vts\Admin\ACLAdminPage($this->path, $this->url),
                'registerEditorPage'
            ]
        );

        // Plugin's Settings
        add_action('admin_init', [
            new \Jk\Vts\Admin\Settings(),
            'register'
        ]);

        // override edit post link for aim clip list allowing for our custom editor
        add_filter(
            'get_edit_post_link',
            [new \Jk\Vts\Admin\ACLAdminPage($this->path, $this->url), 'postTypeEditorLink'],
            10,
            2,
        );
        add_action(
            'current_screen',
            [new \Jk\Vts\Admin\ACLAdminPage($this->path, $this->url), 'redirectNewPost'],
            10,
            1
        );

        // assets for admin
        add_action(
            'admin_enqueue_scripts',
            [
                new \Jk\Vts\Admin\AdminPage($this->path, $this->url),
                'enqueueAsset'
            ]
        );

        // assets for aim clip list editor
        add_action(
            'admin_enqueue_scripts',
            [
                new \Jk\Vts\Admin\ACLAdminPage($this->path, $this->url),
                'enqueueAsset'
            ]
        );

        // assets for frontend
        add_action(
            'wp_enqueue_scripts',
            [
                new \Jk\Vts\Public\Assets($this->path, $this->url),
                'enqueueAssets'
            ]
        );

        // ASSETS FOR VITE
        add_action(
            'admin_enqueue_scripts',
            [new Vite($this->url, $this->path), 'viteRuntime']
        );
        add_filter(
            'script_loader_tag',
            [new Vite($this->url, $this->path), 'use_esm_modules'],
            10,
            3
        );
    }
}
