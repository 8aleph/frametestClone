<?php

namespace Mini\Exception;

use ErrorException;
use Exception;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Throwable;

/**
 * Initialize the application exception handler.
 */
class ExceptionBootstrap
{
    /**
     * Reserved memory so that errors can be displayed properly on memory exhaustion.
     *
     * @var string
     */
    public static $reservedMemory = '';

    /**
     * IoC container.
     *
     * @var Mini\Container|null
     */
    protected $container = null;

    /**
     * Boot up the exception handler.
     *
     * @param Container $container
     * 
     * @return void
     */
    public function boot(Container $container): void
    {
        self::$reservedMemory = str_repeat('x', 10240);

        $this->container = $container;

        error_reporting(-1);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);

        if (!is_testing()) {
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Convert PHP errors to ErrorException instances.
     *
     * @param int    $level   level of the error
     * @param string $message error message
     * @param string $file    file that the error occurred in
     * @param int    $line    line that the error occurred on
     * @param array  $context optional extra error information
     * 
     * @return void
     *
     * @throws ErrorException
     */
    public function handleError(
        int $level, 
        string $message, 
        string $file = '', 
        int $line = 0, 
        array $context = []
    ): void {
        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    /**
     * Handle an uncaught exception from the application.
     *
     * @param Throwable $e exception
     * 
     * @return void
     */
    public function handleException(Throwable $e): void
    {
        try {
            self::$reservedMemory = null;

            $this->getExceptionHandler()->log($e);
        } catch (Exception $e) {
            // 
        }

        // Render the exception response and then send it back to the browser
        $this->getExceptionHandler()->render($this->container->getParameter('request'), $e)->send();
    }

    /**
     * Handle the PHP shutdown event.
     *
     * @return void
     */
    public function handleShutdown(): void
    {
        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException($this->fatalErrorFromPhpError($error, 0));
        }
    }

    /**
     * Create a new fatal error instance from an error array.
     *
     * @param array    $error       php error
     * @param int|null $traceOffset error trace offset
     * 
     * @return FatalError fatal error
     */
    protected function fatalErrorFromPhpError(array $error, ?int $traceOffset = null): FatalError
    {
        return new FatalError($error['message'], 0, $error, $traceOffset);
    }

    /**
     * Determine if the error type is fatal.
     *
     * @param int $type error type
     * 
     * @return bool whether it is fatal
     */
    protected function isFatal(int $type): bool
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }

    /**
     * Get an instance of the exception handler.
     *
     * @return ExceptionHandler handler
     */
    protected function getExceptionHandler(): ExceptionHandler
    {
        return $this->container->get(ExceptionHandler::class);
    }
}
