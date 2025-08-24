<?php

namespace Jk\Vts\Services;

use Illuminate\Support\Collection;
use GuzzleHttp\Client;

class ReRanker {
    public Client $client;
    private string $apiKey;
    public function __construct(

        public string $apiURL = "https://cloudflare-reranker.space-monkeys.workers.dev",
    ) {
        $this->apiKey = get_option('reranker-api-key');

        $this->client = new Client([
            'base_uri' => $this->apiURL,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
        ]);
    }

    public function rerank(string $query, array $docs, $take = 10): array {
        $data = [
            'query' => $query,
            'documents' => $docs,
        ];
        try {
            $response = $this->client->request('POST', "/reranker", [
                'json' => $data, // 'json' option automatically encodes the data and sets Content-Type header
            ]);

            $body = json_decode($response->getBody()->getContents());
            return collect($body)->take($take)->toArray();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return collect($docs)->take($take)->toArray();
        }
    }
}
