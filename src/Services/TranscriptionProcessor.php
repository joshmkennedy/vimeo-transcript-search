<?php

namespace Jk\Vts\Services;

use Illuminate\Support\Collection;
use Jk\Vts\DTO\VimeoTranscriptionChunkWithEmbed;

class TranscriptionProcessor {
	public function __construct(private Embed $embedder) {
	}

	public function prepareTranscript(array $input) {
		$title = $input['title'];
		$vimeoId = $input['videoId'];
		$collection = new Collection();
		foreach ($input['transcript'] as $i => $chunk) {
			// 1. Fix timestamps
			list($start_time, $end_time) = $this->normalizeTimestamp(
				$chunk["ts"],
				$i < count($input['transcript']) ? $input['transcript'][$i + 1]["ts"] : null
			);

			// 2. Create Embedding
			list($error, $embedding) = $this->embedder->createEmbed($chunk['content']);
			if ($error) {
				throw new \Exception($error);
			}

			// 3. Collect prepared data
			$collection->push(new VimeoTranscriptionChunkWithEmbed(
				title: $title,
				vimeoId: $vimeoId,
				content: $chunk['content'],
				start_time: $start_time,
				end_time: $end_time,
				embedding: $embedding,
			));
		}
		return $collection;
	}

	private function normalizeTimestamp(string $start, ?string $end): array {
		$times = [
			$this->timestampToSeconds($start),
		];
		if ($end) {
			$times[] = $this->timestampToSeconds($end);
		}
		return $times;
	}

	protected function timestampToSeconds(string $timestamp): int {
		if (str_contains($timestamp, ':')) {
			$parts = explode(':', $timestamp);
			$minutes = (int) ($parts[0] ?? 0);
			$seconds = (int) ($parts[1] ?? 0);
			return ($minutes * 60) + $seconds;
		} else {
			return (int) $timestamp;
		}
	}
}
