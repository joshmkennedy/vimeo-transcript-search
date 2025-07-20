<?php

namespace Jk\Vts\Services;

use GuzzleHttp\Client;
use Orhanerday\OpenAi\OpenAi;


class Chat {
    public Client $client;
    private OpenAi $openai;
    public function __construct(
        public string $apiURL = "http://localhost:11434/",
        public string $model = "llama2"
    ) {
        $this->client = new Client([
            'base_uri' => $this->apiURL,
        ]);

        $this->openai = new OpenAi(
            OPENAI_API_KEY: get_option('openai-api-key'),
        );
    }

    public function queryChunks(string $query, array $chunks,  bool $useOpenAi = true): array {
        if ($useOpenAi) {
            return $this->queryChunks__openai($query, $chunks);
        } else {
            return $this->queryChunks__ollama($query, $chunks);
        }
    }

    public function queryChunks__openai(string $query, array $chunks): array {
        error_log(print_r($chunks[0], true));
        try {
            $json = $this->openai->chat([
                'model' => 'gpt-4-turbo',
                'messages' => [
                    $this->systemPrompt(),
                    $this->userPrompt($query, $chunks),
                ],
                'temperature' => 0.7,
                'max_tokens' => 1000,
            ]);
            $results = json_decode($json);
            error_log("OPEN AI RESPONSE\n");
            error_log(print_r($results, true));

            if (property_exists($results, 'error')) {
                throw new \RuntimeException($results->error->message);
            }

            $content = $results->choices[0]->message->content;
            $videos = json_decode($content);

            return $videos;
        } catch (\Exception $e) {
            return [];
        } catch (\RuntimeException $e) {
            error_log($e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function queryChunks__ollama(string $query, array $chunks): array {
        // Send a POST request
        $data = [
            'model' => $this->model,
            'messages' => [
                $this->systemPrompt(),
                $this->userPrompt($query, $chunks),
            ],
        ];
        try {
            $response = $this->client->request('POST', "/v1/chat/completions", [
                'json' => $data, // 'json' option automatically encodes the data and sets Content-Type header
            ]);

            $body = json_decode($response->getBody()->getContents());
            if (property_exists($body, 'error')) {
                throw new \RuntimeException($body->error->message);
            }
            error_log(print_r($body, true));
            die;
            return $body->data->choices[0]->message->content;
        } catch (\Exception $e) {
            return [];
        } catch (\RuntimeException $e) {
            error_log($e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    private function systemPrompt(): array {
        return [
            'role' => 'system',
            'content' => "
                # SUMMARY
                You are a helpful video clip search assistant. You will take in a list of found video clips each with a title, id, start and end time, and a transcript. You will then look through the transcripts and find the most relevant results. then once you have the top 3 return those

                # INPUT
                - query: the user is looking for answers to a question or they may wish to find video clips about a specific topic.

                # OUTPUT
                The top 3 videos that you think would best make a good social media post that answers or satisfies the user's query. in the response for each video you will include the vimeoId, start_time, and end_time.

                # FORMATTING
                RESPOND IN JSON FORMAT! here is an example output:
                '[{\"vimeoId\": \"123\", \"start_time\": 123, \"end_time\": \"456\" }, {\"vimdeoId\": \"456\", \"start_time\": 456, \"end_time\": \"789\" }, {\"vimeoId\": \"789\", \"start_time\": 789, \"end_time\": \"101112\" }]'

                Only out put valid JSON, as this will be used in an API response.
            ",
        ];
    }

    private function userPrompt(string $query, array $chunks): array {
        $prompt = "Question: {$query}\n\nContext:\n";
        $prompt .= json_encode($chunks);
        return [
            'role' => 'user',
            'content' => $prompt,
        ];
    }
}
