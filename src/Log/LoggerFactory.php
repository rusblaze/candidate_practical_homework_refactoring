<?php

namespace Language\Log;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Business logic related to generating language files.
 */
class LoggerFactory
{
    protected static $loggers = [];

    public static function get($name, $type = 'stream')
    {
        if (!isset(static::$loggers[$type][$name])) {
            switch ($type) {
                default:
                    $nandlers = [
                        new StreamHandler('php://stdout', Logger::DEBUG),
                        new StreamHandler('php://stderr', Logger::ERROR),
                    ];
                    $processors = [new PsrLogMessageProcessor];

                    // Create the logger
                    $logger = new Logger($name, $nandlers, $processors);

                    static::$loggers[$type][$name] = $logger;
            }
        }

        return static::$loggers[$type][$name];
    }

}
