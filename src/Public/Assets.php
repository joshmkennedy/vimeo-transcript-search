<?php

namespace Jk\Vts\Public;

use Jk\Vts\Services\Vite;

class Assets {
    protected \Jk\Vts\Services\Vite $assets;

    // TODO: use this?
    const NONCE = "vts-admin-none";

    public function __construct(string $pluginPath, string $pluginUrl) {
        $this->assets = new Vite(plugin_path: $pluginPath, plugin_url: $pluginUrl);
    }

    private function shouldLoad(){
        // for now we will always load.
        return true;
    }

    public function enqueueAssets() {
        if (!$this->shouldLoad()) return;
        $handle = $this->assets->use('assets/src/frontend.ts');
        wp_localize_script($handle, 'vtsPublic', [
            'nonce' => wp_create_nonce("wp_rest"),
            // 'apiUrl' => get_rest_url(null, "/vts/v1"),
            'aimClip'=> get_query_var('aim-clip') ?? false,
        ]);

        $this->assets->viteRuntime();
    }
}
