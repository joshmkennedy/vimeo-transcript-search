<?php

namespace Jk\Vts\Endpoint;

class UploadTranscript {
	protected string $namespace = 'vts/v1';
	protected string $route = 'upload-transcript';

	public function register() {
		register_rest_route(
			$this->namespace,
			$this->route,
			[
				'methods' => 'POST',
				'callback' => [$this, 'handle'],
				'permission_callback' => [$this, 'permission'],
			]
		);
	}

	protected function permission() {
		return current_user_can('manage_options');
	}

	protected function handle() {
		return [
			'status' => 'ok',
			'message' => 'Transcript uploaded successfully.',
		];
	}
}
