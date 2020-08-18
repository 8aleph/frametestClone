<?php

declare(strict_types = 1);

namespace Mini\Http\Exception;

use Throwable;

/**
 * HTTP exception.
 */
class HttpException extends \RuntimeException
{
    /**
     * HTTP status code.
     * 
     * @var int|null
     */
    protected $statusCode = null;

    /**
     * HTTP headers.
     * 
     * @var array
     */
    protected $headers = [];

    /**
     * Setup.
     * 
     * @param int            $statusCode http status code
     * @param string|null    $message    error message
     * @param Throwable|null $previous   previous error
     * @param array          $headers    http headers
     * @param int|integer    $code       error code
     */
    public function __construct(
        int $statusCode,
        string $message = null,
        Throwable $previous = null,
        array $headers = [],
        ?int $code = 0
    ) {
        $this->statusCode = $statusCode;
        $this->headers    = $headers;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the HTTP status code.
     * 
     * @return int|null status code
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Get the HTTP headers.
     * 
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set the HTTP headers.
     *
     * @param array $headers http headers
     *
     * @return void
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
}
