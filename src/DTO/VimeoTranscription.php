<?php

namespace Jk\Vts\DTO;

use Illuminate\Support\Collection;

class VimeoTranscription {

	public function __construct(
		public string $title,
		public string $videoId,

		public Collection $transcript,
	) {
	}
}
