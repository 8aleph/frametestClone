<?php

declare(strict_types = 1);

namespace Mini\Log;

use Exception;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Logger to write warnings and errors.
 */
class Logger
{
    /**
     * Logger instance.
     * 
     * @var Monolog\Logger|null
     */
    protected $logger = null;

    /**
     * Setup the logger.
     * 
     * @return void
     */
    protected function setupLogger(): void
    {   
        $streamHandler = new StreamHandler($this->getLoggerPath(), Monolog::INFO);
        $streamHandler->setFormatter($this->getLoggerStreamFormat());

        $this->logger = new Monolog('app');
        $this->logger->pushHandler($streamHandler);
    }

    /**
     * Get the logger path.
     *
     * Note: Defaulting everything to stderr.
     * 
     * @return string logger path
     */
    protected function getLoggerPath(): string
    {
        return 'php://stderr';
    }

    /**
     * Setup the logger line format.
     * 
     * @return LineFormatter file line format
     */
    protected function getLoggerStreamFormat(): LineFormatter
    {
        $dateFormat = "m/d/Y H:i A";
        $output = "%datetime% * %level_name%: %message%\n";

        return new LineFormatter($output, $dateFormat);
    }

    /**
     * Feed calls into the logger.
     * 
     * @param string $method    method to call
     * @param array  $arguments method arguments
     * 
     * @return void
     */
    public function __call(string $method, array $arguments): void
    {
        if ($this->logger === null) {
            $this->setupLogger();
        }

        $this->logger->{$method}(...$arguments);
    }
}
