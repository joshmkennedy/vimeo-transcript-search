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

        // ADMIN
        add_action(
            'admin_menu',
            [
                new \Jk\Vts\Admin\AdminPage($this->path, $this->url),
                'register'
            ]
        );
        add_action('admin_init', [
            new \Jk\Vts\Admin\Settings(),
            'register'
        ]);

        // assets for admin
        add_action(
            'admin_enqueue_scripts',
            [
                new \Jk\Vts\Admin\AdminPage($this->path, $this->url),
                'enqueueAsset'
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
