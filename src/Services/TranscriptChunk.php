<?php

namespace Jk\Vts\Services;

use Illuminate\Support\Collection;

class TranscriptChunk {
    private DB $db;
    private array $rows;

    public function __construct() {
        $this->db = DB::initLocal();
        $this->rows = [];
    }
    public function get(string $vimeoId, int $startTime, int $endTime) {
        $sql = <<<SQL
        SELECT
            tc.start_time,
            tc.title,
            tc.content,
            l.summary,
            l.key_points,
            l.topic_tags
        FROM transcript_chunks tc
        JOIN chunk_labels_new l ON l.chunk_id = tc.id
        WHERE tc.vimeoId = ?
        AND tc.start_time >= ?
        AND tc.end_time <= ?
        ORDER BY tc.start_time
SQL;
        $results = $this->db->query($sql, [
            ['type' => 'text', 'value' => $vimeoId],
            ['type' => 'integer', 'value' => (string)$startTime],
            ['type' => 'integer', 'value' => (string)$endTime],
        ]);
        if (!$results) {
            throw new \Exception("No results, from db for vimeoId: $vimeoId, startTime: $startTime, endTime: $endTime");
        }
        $this->rows = $results;
        return $this;
    }
    public function concatOn(string $field) {
        if (!$this->rows || count($this->rows) < 1) {
            throw new \Exception("No rows, in " . __CLASS__);
        }
        if (!isset($this->rows[0][$field])) {
            throw new \Exception("Field $field not found in rows");
        }
        return Collection::make($this->rows)->map(fn($row) => $row[$field])->implode("\n");
    }
    public function collect() {
        return Collection::make($this->rows);
    }
    public function topics() {
        return $this->collect()->map(fn($row) => $this->dbArray($row['topic_tags']))->flatten()->unique()->toArray();
    }
    public function keyPoints(){
        return $this->collect()->map(fn($row) => $this->dbArray($row['key_points']))->flatten()->unique()->toArray();
    }

    private function dbArray(string $topicTags) {
        $tags = [];
        try {
            $tags = json_decode($topicTags, true);
        } catch (\Exception) {
            $tags = collect(explode(' ', $topicTags))->map(fn($tag) => str_replace(',', '', $tag))->toArray();
        }
        return array_filter(array_map(fn($tag) => str_replace("ai-", "", trim($tag)), $tags), fn($tag) => strlen($tag) > 0);
    }
}
