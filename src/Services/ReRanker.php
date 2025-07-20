<?php

namespace Jk\Vts\Services;
use Illuminate\Support\Collection;
use GuzzleHttp\Client;

class ReRanker {
    public Client $client;
    public function __construct(
        public string $apiURL = "http://localhost:3344/",
    ) {

        $this->client = new Client([
            'base_uri' => $this->apiURL,
        ]);
    }

    public function rerank(string $query, array $docs): array {
        $data = [
            'query' => $query,
            'documents' => $docs,
        ];
        try {
            $response = $this->client->request('POST', "/rerank", [
                'json' => $data, // 'json' option automatically encodes the data and sets Content-Type header
            ]);

            $body = json_decode($response->getBody()->getContents());
            return collect($body->ranked_documents)->take(10)->toArray();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return collect($docs)->take(10)->toArray();
        }
    }
}
