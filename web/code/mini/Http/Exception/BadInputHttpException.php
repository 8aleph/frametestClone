<?php

declare(strict_types = 1);

namespace Mini\Http\Exception;

use Throwable;

/**
 * Bad Input HTTP exception.
 */
class BadInputHttpException extends HttpException
{
    /**
     * Setup.
     * 
     * @param string|null    $message  error message
     * @param Throwable|null $previous previous error
     * @param int            $code     error code
     * @param array          $headers  http headers
     */
    public function __construct(
    	string $message = null,
    	Throwable $previous = null,
    	int $code = 0,
    	array $headers = []
    ) {
        parent::__construct(400, $message, $previous, $headers, $code);
    }
}
