<?php

namespace Jk\Vts\Endpoint;

use Jk\Vts\DTO\VimeoTranscriptionChunkWithEmbed;
use Jk\Vts\Services\Embed;
use Jk\Vts\Services\TranscriptionProcessor;
use Jk\Vts\Services\Db;

/** @package Jk\Vts\Endpoint */
class UploadTranscript {
	protected string $namespace = 'vts/v1';
	protected string $route = 'upload-transcript';

	protected TranscriptionProcessor $processor;
	protected Embed $embbeder;
	protected Db $db;

	public function __construct() {
		// THESE NEED TO BE CHANGEABLE
		$this->embbeder = new Embed();
        try{
		$this->db = Db::initLocal();
        } catch(\Exception $e) {
            error_log($e->getMessage());
            return;
        }

		$this->processor = new TranscriptionProcessor($this->embbeder);
	}

	public function register() {
		register_rest_route(
			$this->namespace,
			$this->route,
			[
				'methods' => 'POST',
				'callback' => [$this, 'post'],
				'permission_callback' => [$this, 'permission'],
			]
		);
	}

	public function permission() {
		return current_user_can('manage_options');
	}

	public function post(\WP_REST_Request $request) {
		$body = json_decode($request->get_body(), true);
		$error = $this->validateInput($body);
		if ($error instanceof \WP_Error) {
			return rest_ensure_response($error);
		}

		try {
			// ensure we have a db
            // TODO: this should be a GENIE migration
			$this->db->createTranscriptChunksTable();

			$this->processor
				// Normalize and create Embedding for each chunk
				->prepareTranscript($body)
				// insert each chunk to db
				->each([$this->db, 'insertTranscriptEmbed']);

		} catch (\Exception $e) {
            error_log($e->getMessage());
			return rest_ensure_response(new \WP_Error('invalid_transcript', $e->getMessage(), array('status' => 400)));
		}

		return rest_ensure_response(['status' => 'ok']);
	}

	private function validateInput(?array $body) {
		if (! $body) {
            error_log("body is empty");
			return new \WP_Error('invalid_body', 'Request body is missing or invalid.', ['status' => 400]);
		}
		$title = isset($body['title']) ? $body['title'] : null;
		if (is_null($title) || empty($title)) {
            error_log("title is empty");
			return new \WP_Error('invalid_title', 'Title is required', array('status' => 400));
		}
		$videoId = isset($body['videoId']) ? $body['videoId'] : null;
		if (empty($videoId)) {
            error_log("videoId is empty");
			return new \WP_Error('invalid_videoId', 'Video ID is required', array('status' => 400));
		}
		$transcript = isset($body['transcript']) ? $body['transcript'] : null;
		if (empty($transcript) || !is_array($transcript)) {
            error_log("transcript is empty");
			return new \WP_Error('invalid_transcript', 'Transcript is required and should be an array', array('status' => 400));
		}
		if (count($transcript) < 1) {
            error_log("count transcript < 1");
			return new \WP_Error('invalid_transcript', 'Transcript should have at least one item', array('status' => 400));
		}
	}

}
