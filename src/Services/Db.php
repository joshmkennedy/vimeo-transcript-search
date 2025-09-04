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

    // TODO: make this private
    public function query(string $sql, array $params = [], $named=false) {
        $requests = [];

        $stmt = ['sql' => $sql];
        if (!empty($params) && !$named) {
            $stmt['args'] = $params;
        }
        if(!empty($params) && $named) {
            $stmt['named_args'] = $params;
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
            } else {
                throw new \Exception(print_r($data, true));
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
            ['type' => 'text', 'value' => $data->title],
            ['type' => 'text', 'value' => $data->vimeoId],
            ['type' => 'text', 'value' => $data->content],
            ['type' => 'float', 'value' => $data->start_time],
            property_exists($data, "end_time") ? ['type' => 'float', 'value' => $data->end_time] : null,
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

    public function upsertChunkLabel(array $data): void {
        // $data keys expected:
        // chunk_id (int), labeling_version (string)
        // topic_tags (array|string), intent (nullable string)
        // usefulness_score (nullable float 0..1), level (nullable string)
        // summary (nullable string), key_points (array|string|null)
        // confidence (nullable float 0..1), video_type ('lecture'|'lab')

        $topicTags = is_array($data['topic_tags'])
            ? json_encode($data['topic_tags'])
            : (string)$data['topic_tags'];

        $keyPoints = isset($data['key_points'])
            ? (is_array($data['key_points']) ? json_encode($data['key_points']) : (string)$data['key_points'])
            : null;

        $params = [
            ['type' => 'integer',   'value' => (string)$data['chunk_id']],
            ['type' => 'text',  'value' => $data['labeling_version']],
            ['type' => 'text',  'value' => $topicTags],
            ['type' => 'text',  'value' => $data['intent'] ?? null],
            ['type' => 'float', 'value' => $data['usefulness_score'] ?? null],
            ['type' => 'text',  'value' => $data['level'] ?? null],
            ['type' => 'text',  'value' => $data['summary'] ?? null],
            ['type' => 'text',  'value' => $keyPoints],
            ['type' => 'float', 'value' => $data['confidence'] ?? null],
            ['type' => 'text',  'value' => $data['video_type'] ?? 'lecture'],
        ];

        $sql = <<<SQL
    INSERT INTO chunk_labels_new (
      chunk_id, labeling_version, topic_tags, intent, usefulness_score, level, summary, key_points, confidence, video_type
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON CONFLICT(chunk_id) DO UPDATE SET
      topic_tags = excluded.topic_tags,
      intent = excluded.intent,
      usefulness_score = excluded.usefulness_score,
      level = excluded.level,
      summary = excluded.summary,
      key_points = excluded.key_points,
      confidence = excluded.confidence,
      video_type = excluded.video_type;
    SQL;

        $this->query($sql, $params);
    }
}
