<?php

namespace Jk\Vts\Endpoint;

use Jk\Vts\Services\Db;
use Jk\Vts\Services\Embed;

class SearchTranscriptionEmbeds {
	protected string $namespace = 'vts/v1';
	protected string $route = 'search-transcription-embeds';

	// TODO: chnage these
	protected Db $db;
	protected Embed $embedder;

	public function __construct() {
		$this->db = Db::initLocal();
		$this->embedder = new Embed();
	}

	public function register() {
		register_rest_route(
			$this->namespace,
			$this->route,
			[
				'methods' => 'GET',
				'callback' => [$this, 'get'],
				'permission_callback' => [$this, 'permission'],
				'args' => [
					'query' => [
						'type' => 'string',
						'required' => true,
					],
				],
			]
		);
	}

	public function permission() {
		return current_user_can('read');
	}

	public function get(\WP_REST_Request $request) {
		$query = $request->get_param('query');
		if (empty($query)) {
			return rest_ensure_response(new \WP_Error('invalid_query', 'Query is required', array('status' => 400)));
		}

		list($err, $embeddedQuery) = $this->embedder->createEmbed($query);
		if ($err) {
			return rest_ensure_response(new \WP_Error('invalid_query', $err, array('status' => 400)));
		}

		$results = $this->db->search($embeddedQuery);
		return rest_ensure_response($this->formatResults($results));
	}

	private function formatResults(array $results): array {
		return collect($results)
			->map(function ($result) {
				$result['score'] = floor(((2 - $result["relative_distance"]) / 2) * 100);
				unset($result['relative_distance']);
				$result['iframeSrcUrl'] = "https://player.vimeo.com/video/" . $result['vimeoId'];
				return $result;
			})
			->toArray();
	}
}
