<?php

namespace Jk\Vts\Endpoint;

use Jk\Vts\Services\Db;


class ListTranscribedVideos {
	protected string $namespace = 'vts/v1';
	protected string $route = 'list-transcribed-videos';

	protected Db $db;

	public function __construct() {
		$this->db = Db::initLocal();
	}

	public function register() {
		register_rest_route(
			$this->namespace,
			$this->route,
			[
				'methods' => 'GET',
				'callback' => [$this, 'get'],
				'permission_callback' => [$this, 'permission'],
			]
		);
	}

	public function permission() {
		return current_user_can('manage_options');
	}

	public function get() {
		$videos = $this->db->collectTranscribedVideos();
		return rest_ensure_response($videos);
	}
}
