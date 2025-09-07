<?php

namespace Jk\Vts\Services;

class Cache {
    public function __construct(
        private callable $keyGenerator,
        private callable $generate,
        public int $cacheTime
    ) {

    }

    public function get(mixed ...$args) {
        $key = call_user_func_array([$this, 'keyGenerator'], $args);
        if(false === ($value = get_transient($key))) {
            $value = call_user_func_array([$this, 'generate'], $args);
            set_transient($key, $value, $this->cacheTime);
        }
        return $value;
    }
}
