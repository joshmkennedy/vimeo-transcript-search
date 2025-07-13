<?php

namespace Jk\Vts\Services;

use GuzzleHttp\Client;

class Embed {
	public Client $client;
	public function __construct(
		public string $apiURL = "http://localhost:11434/",
		public string $model = "mxbai-embed-large"
	) {
		$this->client = new Client([
			'base_uri' => $this->apiURL,
		]);
	}

	public function createEmbed(string $text): array {
		// Send a POST request
		$data = [
			'model' => $this->model,
			'input' => $text,
		];
		try {

			$response = $this->client->request('POST', "/v1/embeddings", [
				'json' => $data, // 'json' option automatically encodes the data and sets Content-Type header
			]);

			$body = json_decode($response->getBody()->getContents());

			return [null, $body->data[0]->embedding];
		} catch (\Exception $e) {
			return [$e->getMessage(), null];
		}
	}
}
