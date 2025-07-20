<?php

namespace Jk\Vts\Services;

use GuzzleHttp\Client;
use Jk\Vts\DTO\VimeoTranscriptionChunkWithEmbed;

class DB {
    private Client $http;
    private string $url;
    private string $key;

    public function __construct(string $url, string $key) {
        $this->url = $url;
        $this->key = $key;
        $this->http = new Client([
            'base_uri' => $this->url,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type' => 'application/json'
            ],
            'timeout' => 10,
        ]);
    }

    static public function initLocal() {
        $url = get_option('vts-turso-url');
        $key = get_option('vts-turso-key');

        if (empty($url) || empty($key)) {
            return null;
        }

        return new self($url, $key);
    }

    private function query(string $sql, array $params = []) {
        $requests = [];

        $stmt = ['sql' => $sql];
        if (!empty($params)) {
            $stmt['args'] = $params;
        }

        $requests[] = ['type' => 'execute', 'stmt' => $stmt];
        $requests[] = ['type' => 'close'];

        $body = ['requests' => $requests];

        try {
            $response = $this->http->post('/v2/pipeline', ['json' => $body]);
            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['results'][0]['response']['result']['rows'])) {
                $rows = $data['results'][0]['response']['result']['rows'];
                $cols = $data['results'][0]['response']['result']['cols'];
                $results = [];
                foreach ($rows as $row) {
                    $result = [];
                    foreach ($cols as $index => $col) {
                        $cell = $row[$index] ?? null;
                        if (isset($cell['type']) && $cell['type'] === 'blob') {
                            $result[$col['name']] = base64_decode($cell['value']);
                        } else {
                            $result[$col['name']] = $cell['value'] ?? null;
                        }
                    }
                    $results[] = $result;
                }
                return $results;
            }
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseBodyAsString = $response->getBody()->getContents();
            error_log('Turso API Error: ' . $responseBodyAsString);
            error_log('Request Body: ' . json_encode($body));
            throw $e;
        }

        return null;
    }

    public function insertTranscriptEmbed(VimeoTranscriptionChunkWithEmbed $data): void {
        if (!property_exists($data, 'embedding') || $data->embedding === null) {
            throw new \Exception("No embedding");
        }


        $preparedEmbed = [
            'type' => "text",
            'value' => json_encode($data->embedding),
        ];

        $params = [
            ['type'=>'text', 'value' => $data->title],
            ['type'=>'text', 'value' => $data->vimeoId],
            ['type'=>'text', 'value' => $data->content],
            ['type'=>'float', 'value' => $data->start_time],
            property_exists($data, "end_time") ? ['type'=>'float', 'value' => $data->end_time] : null,
            $preparedEmbed,
        ];

        $sql = <<<SQL
				INSERT INTO transcript_chunks (title, vimeoId, content, start_time, end_time, embedding)
				VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT(content) DO NOTHING;
				SQL;
        $this->query($sql, $params);
    }

    function search(mixed $embedding): array {
        $limit = \Jk\Vts\Endpoint\SearchTranscriptionEmbeds::RERANK ? 100 : 20;
        $limit = ['type' => 'integer', 'value' => (string)$limit];

        $pre_embedding = [
            'type' => "text",
            'value' => json_encode($embedding),
        ];



        $sql = <<<SQL
		SELECT
			title,
			vimeoId,
			vector_distance_cos(embedding, vector32(?)) as relative_distance,
			content,
			start_time,
			end_time
		FROM
			transcript_chunks
		ORDER BY vector_distance_cos(embedding, vector32(?))
		ASC LIMIT ?;
		SQL;
        $results = $this->query($sql, [$pre_embedding, $pre_embedding, $limit]);
        return $results ?? [];
    }

    public function createDB() {
        $this->createTranscriptChunksTable();
    }

    public function createTranscriptChunksTable(): void {
        $sql = <<<SQL
		CREATE TABLE IF NOT EXISTS transcript_chunks (
			id INTEGER	PRIMARY	KEY AUTOINCREMENT,
			vimeoId	TEXT NOT NULL,
			title TEXT NOT NULL,
			content TEXT NOT NULL UNIQUE,
			start_time REAL NOT NULL,
			end_time REAL,
			embedding F64_BLOB NOT NULL
		);
		SQL;
        $this->query($sql);
    }

    public function collectTranscribedVideos() {
        $sql = "SELECT vimeoId, title, COUNT(vimeoId) as chunk_count FROM transcript_chunks GROUP BY vimeoId;";
        $results = $this->query($sql);
        return $results ?? [];
    }
}

