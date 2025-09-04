<?php

namespace Jk\Vts\Services\Logging;

use Monolog\Logger;

trait LoggerTrait {
    private ?Logger $logger = null;

    protected function log(): Logger {
        if ($this->logger === null) {
            // use the class name as the channel
            $this->logger = LoggerFactory::create("JS\VTS");
        }
        return $this->logger;
    }
}
