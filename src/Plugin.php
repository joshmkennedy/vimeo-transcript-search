<?php

namespace Jk\Vts;

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

		// ADMIN
		add_action(
			'admin_menu',
			[
				new \Jk\Vts\Admin\AdminPage(),
				'register'
			]
		);
	}
}
