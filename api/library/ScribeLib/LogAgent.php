<?php

if (!isset($GLOBALS['THRIFT_ROOT'])) {
    $GLOBALS['THRIFT_ROOT'] = dirname(__FILE__) . '/thriftlib';
}
if (!isset($GLOBALS['SCRIBE_ROOT'])) {
    $GLOBALS['SCRIBE_ROOT'] = dirname(__FILE__) . '/Log';
}
require $GLOBALS['SCRIBE_ROOT'] . '/scriber.php';

use Psr\Log\LoggerInterface;
// use Stringable;

class LogAgent implements LoggerInterface
{
    private $config;
    private $scriber;

    public function __construct(array $scribe_servers, array $scribe_ports, $allow = false)
    {
        $this->config = [
            'scribe_servers' => $scribe_servers,
            'scribe_ports' => $scribe_ports,
        ];
        try {
            if ($allow) {
                $this->scriber = new scriber($this->config);
            }
        } catch (Exception $e) {
            // Silently fail
        }
    }

    private function write($level, Stringable|string $message): void
    {
        $category = 'ua-booster-logs';
        try {
            if ($this->scriber) {
                $prefix = strtoupper($level);
                $this->scriber->writeLog($category, "{$prefix}: {$message}\n");
            }
        } catch (Exception $e) {
            // Silently fail
        }
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->write('emergency', $message);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->write('alert', $message);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->write('critical', $message);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->write('error', $message);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->write('warning', $message);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->write('notice', $message);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->write('info', $message);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->write('debug', $message);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->write($level, $message);
    }
}
