<?php

namespace Jk\Vts\Services;

class Vite {
	public string $plugin_url;
	public string $plugin_path;
	public string $module_name;
	public bool $in_dev;
	public string $base_url;
	public array $manifest;

	public function __construct(string $plugin_url, string $plugin_path) {
		$this->plugin_url = $plugin_url;
		$this->plugin_path = $plugin_path;
		$this->module_name = basename($plugin_path);
		$asset_config = $this->asset_info();
		$this->in_dev = $asset_config->env == "development";
		$this->base_url = $this->in_dev ? $asset_config->url : $this->plugin_url . "/assets/build/";

		$this->manifest = $this->in_dev ? [] : $this->get_manifest();
	}

	public function use(string $script = "assets/main.ts", $deps = []): void {
		$this->preload_imports($script);
		if (!$this->in_dev) {
			$this->enqueue_css($script);
		}
		$this->enqueue_js($script, $deps);
	}

	public function viteRuntime() {
		if ($this->in_dev) {
?>
			<script type="module">
				import RefreshRuntime from 'http://localhost:1234/@react-refresh'
				RefreshRuntime.injectIntoGlobalHook(window)
				window.$RefreshReg$ = () => {}
				window.$RefreshSig$ = () => (type) => type
				window.__vite_plugin_react_preamble_installed__ = true
			</script>
<?php
		}
	}

	private function preload_imports(string $script): void {
		$links = "";
		foreach ($this->imports($script) as $url) {
			$links .= "<link rel='modulepreload' href='$url'>";
		}
		add_action("wp_head", function () use (&$links) {
			echo $links;
		});
	}

	private function enqueue_css(string $script): void {
		foreach ($this->css_urls($script) as $url) {
			$handle = sprintf("%s/%s", $this->module_name, $script);
			wp_register_style($handle, $url);
			wp_enqueue_style($handle, $url);
		}
	}

	private function enqueue_js(string $script, $deps): void {
		$handle = sprintf("module/%s/%s", $this->module_name, $script);
		wp_register_script($handle, $this->asset_url($script), $deps,  $this->in_dev);
		wp_enqueue_script($handle);
	}

	private function imports(string $script): array {
		if (empty($this->manifest[$script]['imports'])) return [];

		return array_map(
			fn($import) => $this->base_url . $this->manifest[$import]['file'],
			$this->manifest[$script]['imports']
		);
	}

	private function css_urls(string $entry): array {
		$urls = [];
		if (isset($this->manifest[$entry]['imports'])) {
			foreach ($this->manifest[$entry]['imports'] as $import) {
				$urls = array_merge($urls, $this->css_urls($import));
			}
		}
		if (!empty($this->manifest[$entry]['css'])) {
			foreach ($this->manifest[$entry]['css'] as $file) {
				$urls[] = $this->base_url . $file;
			}
		}
		return $urls;
	}

	private function asset_url(string $entry): string {
		if ($this->in_dev) {
			return $this->base_url . "/" . $entry;
		}
		return isset($this->manifest[$entry])
			? $this->plugin_url . "assets/build/" . $this->manifest[$entry]['file']
			: $this->plugin_url . "assets/build/" . $entry;
	}

	/** @return mixed  */
	private function asset_info() {
		$contents = file_get_contents($this->plugin_path . 'assets/asset-info.json');
		return json_decode($contents);
	}

	private function get_manifest(): array {
		$content = file_get_contents($this->plugin_path . 'assets/build/.vite/manifest.json');
		return json_decode($content, true);
	}
	/**
	 * @return string
	 */
	public function use_esm_modules(string $tag, string $handle, string $src): string {
		if (false !== stripos($handle, 'module')) {
			$str = "type='module'";
			$str .= $this->in_dev ? ' crossorigin' : '';
			$tag = str_replace("type='text/javascript'", $str, $tag);

			return "<script type='module' src='$src' id='$handle' crossorigin></script>";
		} else {
			return $tag;
		}
	}
}
