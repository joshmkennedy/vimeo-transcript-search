<?php

namespace Jk\Vts\Services;

use Jk\Vts\DTO\VimeoTranscriptionChunkWithEmbed;
use Libsql\Database;

class DB {
	const DATABASE_PATH = "/Users/joshkennedy/work/jp/wp-content/plugins/vimeo-transcript-search/dev.db";
	public function __construct(public Database $db) {
	}


	static public function initLocal() {
		$db = new Database(path: self::DATABASE_PATH);
		return new self($db);
	}


	public function insertTranscriptEmbed(VimeoTranscriptionChunkWithEmbed $data): void {
		// TODO: Implement insertBatdch() method.

		if (!property_exists($data, 'embedding') || $data->embedding === null) {
			print_r($data);
			throw new \Exception("No embedding");
		}

		$preparedEmbed = json_encode($data->embedding);
		try {
			if (property_exists($data, "end_time") && ($data->end_time)) {
				$sql = <<<SQL
				INSERT INTO transcript_chunks (title, vimeoId, content, start_time, end_time, embedding)
				VALUES (:title, :vimeoId, :content, :start_time, :end_time, vector32(:embedding)) ON CONFLICT(content) DO NOTHING;
				SQL;
				$this->db->connect()->execute(
					$sql,
					[
						$data->title,
						$data->vimeoId,
						$data->content,
						$data->start_time,
						$data->end_time,
						$preparedEmbed,
					]
				);
			} else {
				$sql = <<<SQL
				INSERT INTO transcript_chunks (title, vimeoId, content, start_time, embedding)
				VALUES (:title, :vimeoId, :content, :start_time, vector32(:embedding)) ON CONFLICT(content) DO NOTHING;
				SQL;
				$this->db->connect()->execute(
					$sql,
					[
						$data->title,
						$data->vimeoId,
						$data->content,
						$data->start_time,
						$preparedEmbed,
					]
				);
			}
		} catch (\Libsql\LibsqlException $e) {
			echo $e->getMessage();
		}
	}

	function search(array $embedding): array {
		$sql = <<<SQL
		SELECT
			title,
			vimeoId,
			vector_distance_cos(embedding, vector32(:query)) as relative_distance,
			content,
			start_time,
			end_time
		FROM
			transcript_chunks
		ORDER BY vector_distance_cos(embedding, vector32(:query))
		ASC LIMIT 3;
		SQL;
		$results = $this->db->connect()->query($sql, [json_encode($embedding)]);
		return $results->fetchArray();
	}


	public function createDB() {
		$this->createTranscriptChunksTable();
		// ... maybe add more ...
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
			embedding F32_BLOB(3336) NOT NULL
		);
		SQL;
		try {
			$this->db->connect()->execute($sql);
		} catch (\Libsql\LibsqlException $e) {
			throw new \Exception($e->getMessage());
		}
	}

	public function collectTranscribedVideos() {
		$sql = <<< SQL
		Select vimeoId, title from transcript_chunks group by vimeoId;
		SQL;
		$results = $this->db->connect()->query($sql);
		return $results->fetchArray();
	}
}
