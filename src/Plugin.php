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
        $this->createApi();
        $this->loaded = true;
    }
    
    //* not rest but programming or integration api
    private function createApi() {
        new \Jk\Vts\Forms\FormDisplay(); // creates global $aimFormDisplay

        global $getAimClipListWeekData;
        $getAimClipListWeekData = fn($clipListId, $weekIndex) => new \Jk\Vts\Services\AimClipList\AimClipListWeekData($clipListId, $weekIndex);

        global $aimClipListUserMeta;
        $aimClipListUserMeta = new \Jk\Vts\Services\AimClipList\AimClipListUserMeta();
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


        // ** REST API **

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

        // AIM CLIP LIST User Endpoints
        add_action(
            'rest_api_init',
            [
                new \Jk\Vts\Endpoint\UserClipListActions(),
                'register'
            ]
        );

        // ** ADMIN PAGES **

        // ADMIN PAGE
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

        // User Cliplist table
        add_action('edit_user_profile', [new \Jk\Vts\Admin\UserClipListPage(), 'render_table']);
        add_action('show_user_profile', fn($user) => user_can($user, "manage_options") ? (new \Jk\Vts\Admin\UserClipListPage())->render_table($user) : "");


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


        // ** ASSETS **

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


        // ** SCHEDULED ACTIONS **

        // AIM Clip list emails
        add_action(
            'init',
            [new \Jk\Vts\Actions\AimClipListJobs($this->path, $this->url), 'scheduleActions']
        );

        // queue clip list emails
        add_action(
            \Jk\Vts\Actions\AimClipListJobs::SEND_EMAILS_ACTION,
            [new \Jk\Vts\Services\AimClipList\AimClipListEmailManager($this->path, $this->url), 'queueEmails'],
            10,
            0
        );
        // send queued clip list email
        add_action(
            \Jk\Vts\Services\AimClipList\AimClipListEmailManager::SEND_QUEUED_EMAILS_ACTION,
            [new \Jk\Vts\Services\AimClipList\AimClipListEmailManager($this->path, $this->url), 'sendEmail'],
            10,
            4
        );
        // send queued registration email
        add_action(
            \Jk\Vts\Services\AimClipList\AimClipListRegistrationEmail::SEND_EMAIL_ACTION,
            [new \Jk\Vts\Services\AimClipList\AimClipListRegistrationEmail($this->path, $this->url), 'sendEmail'],
            10,
            3
        );

        //** FORMS **
        // Clip list signup quiz evaluation and signup.
        //
        add_action(
            'forminator_custom_form_submit_before_set_fields',
            [new \Jk\Vts\Forms\ClipListSignUp($this->path, $this->url), 'handleQuizSubmission'],
            10,
            3
        );
    }
}
