<?php

namespace Jk\Vts;

class WrapWithSentry {
    public function __construct() {}
    public function __invoke($callback) {
        return function(...$args) use ($callback) {
            try {
                $callback(...$args);
            } catch (\Throwable $e) {
                \Sentry\captureException($e);
            }
        };
    }
}
