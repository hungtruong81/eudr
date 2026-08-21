<?php

namespace App\Application\Utility;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Stringable;

class FileLogger implements LoggerInterface
{
    protected $category = 'payment-partner';

    protected $uniqueID;

    public const SEPARATOR = '|';

    public const INFO      = 'INFO';
    public const NOTICE    = 'NOTICE';
    public const DEBUG     = 'DEBUG';
    public const WARNING   = 'WARNING';
    public const ERROR     = 'ERROR';
    public const ALERT     = 'ALERT';
    public const CRITICAL  = 'CRITICAL';
    public const EMERGENCY = 'EMERGENCY';

    private $logger;

    public function __construct()
    {
        $date = date('y-m-d');
        $output = "%message%\n";
        $streamHandler = new StreamHandler(__DIR__ . "/../../../logs/{$date}_log.log");
        $streamHandler->setFormatter(new LineFormatter($output, null, false, true));

        $this->logger = new Logger('console');
        $this->logger->pushHandler($streamHandler);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        // Ensure method exists before calling to avoid fatal error
        if (method_exists($this->logger, $level)) {
            $this->logger->{$level}($message, $context);
        } else {
            $this->logger->info("Invalid log level: $level. Message: $message", $context);
        }
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }
}
