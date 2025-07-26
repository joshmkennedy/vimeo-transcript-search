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
            if (array_key_exists('ts', $chunk)) {
                list($start_time, $end_time) = $this->extractTimestamps($chunk, $i, $input['transcript']);
            } else {
                // support format from ('automatic-speech-recognition', 'Xenova/whisper-tiny.en')
                $start_time = (int) $chunk['timestamp'][0];
                $end_time = (int) $chunk['timestamp'][1];
            }

            // 2. Create Embedding
            list($error, $embedding) = array_key_exists('content', $chunk)
                // supports the old format
                ? $this->embedder->createEmbed($chunk['content'])
                // supports the ('automatic-speech-recognition', 'Xenova/whisper-tiny.en')
                : $this->embedder->createEmbed($chunk['text']);
            if ($error) {
                error_log($error);
                throw new \Exception($error);
            }

            error_log("created embedding");

            // 3. Collect prepared data
            $collection->push(new VimeoTranscriptionChunkWithEmbed(
                title: $title,
                vimeoId: $vimeoId,
                content: array_key_exists('content', $chunk) ? $chunk['content'] : $chunk['text'],
                start_time: $start_time,
                end_time: $end_time,
                embedding: $embedding,
            ));
        }
        error_log("prepared transcript ". $title);
        return $collection;
    }

    /**
     * @deprecated this supports the old format
     **/
    public function extractTimestamps(array $chunk, $index, $chunks): array {
        $isLastChunk = $index === count($chunks) - 1;

        $start_time = $this->timestampToSeconds($chunk['ts']);
        $end_time = $isLastChunk ? null : $this->timestampToSeconds(
            $chunk[$index + 1]['ts'] ?? "",
        );

        return [$start_time, $end_time];
    }

    protected function timestampToSeconds(?string $timestamp): int|null {
        if (!$timestamp) return null;
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
