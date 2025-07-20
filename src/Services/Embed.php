<?php

namespace Jk\Vts\Services;

use GuzzleHttp\Client;
use Orhanerday\OpenAi\OpenAi;

class Embed {
    public Client $client;
    private OpenAi $openai;
    public function __construct(
        public string $apiURL = "http://localhost:11434/",
        public string $model = "mxbai-embed-large"
    ) {
        $this->client = new Client([
            'base_uri' => $this->apiURL,
        ]);

        $this->openai = new OpenAi(
            OPENAI_API_KEY: get_option('openai-api-key'),
        );
    }

    public function createEmbed(string $text, bool $useOpenAi = true): array {
        if ($useOpenAi) {
            return $this->createEmbed__openai($text);
        } else {
            return $this->createEmbed__ollama($text);
        }
    }

    public function createEmbed__openai(string $text): array {
        $result = $this->openai->embeddings([
            'model' => 'text-embedding-3-small',
            'input' => $text,
            'dimensions' => 1024,
        ]);
        $result = json_decode($result);
        return [null, $result->data[0]->embedding];
    }

    public function createEmbed__ollama(string $text): array {
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
