<?php

declare(strict_types = 1);

namespace Mini\Database\Exception;

use Exception;
use Throwable;

/**
 * Database duplicate key insert exception.
 */
class DuplicateInsertException extends Exception
{
    /**
     * Actual database query exception.
     * 
     * @var Throwable|null
     */
    protected $exception = null;

    /**
     * Database query config that produced the error.
     * 
     * @var array
     */
    protected $config = [];

    /**
     * Setup.
     * 
     * @param Throwable      $exception database query exception
     * @param array          $config    query config
     * @param string|null    $message   error message
     * @param Throwable|null $previous  previous error
     * @param int|integer    $code      error code
     */
    public function __construct(
        Throwable $exception,
        array $config,
        string $message = 'Failed to save. Record already exists',
        Throwable $previous = null,
        ?int $code = 0
    ) {
        $this->exception = $exception;
        $this->config    = $config;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the database query exception.
     * 
     * @return Throwable|null exception
     */
    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    /**
     * Get the database query config that produced the error.
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
