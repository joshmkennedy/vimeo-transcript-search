<?php

namespace Jk\Vts\Services;

use Jk\Vts\Services\Logging\LoggerTrait;

class Cache {
    use LoggerTrait;

    public function __construct(
        /** @var callable $keyGenerator */
        private $keyGenerator,
        /** @var callable $generate */
        private $generate,
        public int $cacheTime,
        public ?bool $bypassCache = false,
    ) {

    }

    public function get(mixed ...$args) {
        $key = call_user_func_array($this->keyGenerator, $args);

        // regenerate if not in cache or bypass cache is true
        if(false === ($value = get_transient($key)) || $this->bypassCache) {
            $value = call_user_func_array($this->generate, $args);
            set_transient($key, $value, $this->cacheTime);
        } 
        return $value;
    }
}
