<?php

namespace Jk\Vts\Admin;

use Jk\Vts\Services\Vite;

class AdminPage {
	protected \Jk\Vts\Services\Vite $assets;
	public function __construct(string $pluginPath, string $pluginUrl) {
		$this->assets = new Vite(plugin_path:$pluginPath, plugin_url:$pluginUrl);
	}

	public function register() {
		add_menu_page(
			'Vimeo Transcript Search',
			'Vimeo Transcript Search',
			'manage_options',
			'vimeo-transcript-search',
			[$this, 'render']
		);
	}

	public function render() {
		?>
		<div class="wrap">
			<h1 class="text-red-300">Vimeo Transcript Search</h1>
			<div id="vimeo-transcript-upload-app"></div>
		</div>
		<?php
	}

	public function enqueueAsset(): void {
		$this->assets->use('assets/src/admin.ts');
	}
}
