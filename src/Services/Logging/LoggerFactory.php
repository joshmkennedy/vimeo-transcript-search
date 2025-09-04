<?php
namespace Jk\Vts\Services\Logging;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\JsonFormatter;
use Monolog\Level;
use Monolog\Processor\IntrospectionProcessor;

class LoggerFactory
{
    public static function create(string $channel = 'VTS'): Logger
    {
        $logger = new Logger($channel);

        // Use WP_DEBUG if defined; default to info otherwise
        $level = (defined('WP_DEBUG') && WP_DEBUG) ? Level::Debug : Level::Info;

        // Handler: send all messages of $level or higher to stderr
        $handler = new StreamHandler(WP_CONTENT_DIR. '/aim.log', $level);
        $handler->setFormatter(new JsonFormatter());

        $logger->pushHandler($handler);

        // Processor: adds file/class/function info
        $logger->pushProcessor(new IntrospectionProcessor());

        // Ensure the logger itself allows debug messages
        $logger->setHandlers([$handler]);

        return $logger;
    }
}
