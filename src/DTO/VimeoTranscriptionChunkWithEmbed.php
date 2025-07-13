<?php

namespace Jk\Vts\DTO;

class VimeoTranscriptionChunkWithEmbed {
	public function __construct(
		public string $content,
		public array $embedding,
		public string $vimeoId,
		public string $title,
		public int $start_time,
		public ?int $end_time,
	) {
	}
}
