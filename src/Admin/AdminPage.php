<?php

namespace Jk\Vts\Admin;

use Jk\Vts\Services\Vite;

class AdminPage {
    protected \Jk\Vts\Services\Vite $assets;
    const NONCE = "vts-admin-none";
    const SLUG = "vimeo-transcript-search";
    public function __construct(string $pluginPath, string $pluginUrl) {
        $this->assets = new Vite(plugin_path: $pluginPath, plugin_url: $pluginUrl);
    }

    public function register() {
        add_menu_page(
            'Vimeo Transcript Search',
            'Vimeo Transcript Search',
            'manage_options',
            self::SLUG,
            [$this, 'render']
        );
    }

    public function render() {
?>
        <div class="wrap">
            <div id="vimeo-transcript-upload-app"></div>
        </div>
<?php
    }

    public function enqueueAsset(): void {
        global $current_screen;
        if ($current_screen->base != "toplevel_page_vimeo-transcript-search") return;
        $handle = $this->assets->use('assets/src/admin.ts');
        wp_localize_script($handle, 'vtsAdmin', [
            'nonce' => wp_create_nonce("wp_rest"),
            'apiUrl' => get_rest_url(null, "/vts/v1"),
        ]);
    }
}
