<?php

declare(strict_types = 1);

namespace Mini\Exception;

use Exception;
use Mini\Auth\Exception\AuthenticationException;
use Mini\Auth\Exception\AuthorizationException;
use Mini\Controller\Exception\BadInputException;
use Mini\Database\Exception\DuplicateInsertException;
use Mini\Log\Logger;
use Mini\Http\Exception\AuthenticationHttpException;
use Mini\Http\Exception\AuthorizationHttpException;
use Mini\Http\Exception\BadInputHttpException;
use Mini\Http\Exception\ConflictHttpException;
use Mini\Http\Exception\HttpException;
use Mini\Http\JsonResponse;
use Mini\Http\Request;
use Mini\Http\Response;
use Mini\Routing\Router;
use Mini\Util\Arr;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Throwable;
use Whoops\Run as Whoops;

/**
 * Exception handler.
 */
class ExceptionHandler
{
    /**
     * IoC container.
     *
     * @var Mini\Container|null
     */
    protected $container = null;

    /**
     * A list of the internal exception types that should not be logged.
     *
     * @var array
     */
    protected $internalDontLog = [
        AuthenticationException::class,
        AuthorizationException::class,
        BadInputException::class,
        HttpException::class,
        HttpResponseException::class
    ];

    /**
     * Setup.
     */
    public function __construct()
    {
        $this->container = container();
    }

    /**
     * Log an exception.
     *
     * @param Throwable $e exception
     * 
     * @return void
     */
    public function log(Throwable $e): void
    {
        if (!$this->shouldLog($e)) {
            return;
        }

        if (is_callable($reportCallable = [$e, 'report'])) {
            $this->container->call($reportCallable);
            return;
        }

        $this->container->get(Logger::class)->error(
            $this->getExceptionMessageToLog($e),
            ['exception' => $e]
        );
    }

    /**
     * Determine if the exception should be logged.
     *
     * @param Throwable $e exception
     * 
     * @return bool whether to log
     */
    public function shouldLog(Throwable $e): bool
    {
        // Confirm this is not one of the do not log exceptions
        foreach ($this->internalDontLog as $type) {
            if ($e instanceof $type) {
                return false;
            }
        }

        return true;
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request   $request http request
     * @param Throwable $e       exception
     * 
     * @return BaseResponse http response
     */
    public function render(Request $request, Throwable $e): BaseResponse
    {
        if (method_exists($e, 'render') && $response = $e->render($request)) {
            return Router::createResponse($request, $response);
        }

        $e = $this->prepareException($e);

        return $request->expectsJson()
            ? $this->prepareJsonResponse($request, $e)
            : $this->prepareResponse($request, $e);
    }

    /**
     * Prepare exception for rendering.
     *
     * @param Throwable $e exception
     * 
     * @return Throwable $e prepared exception
     */
    protected function prepareException(Throwable $e): Throwable
    {
        if ($e instanceof AuthenticationException) {
            $e = new AuthenticationHttpException($e->getMessage(), $e);
        } elseif ($e instanceof AuthorizationException) {
            $e = new AuthorizationHttpException($e->getMessage(), $e);
        } elseif ($e instanceof BadInputException) {
            $e = new BadInputHttpException($e->getMessage(), $e);
        } elseif ($e instanceof DuplicateInsertException) {
            $e = new ConflictHttpException($e->getMessage(), $e);
        }

        return $e;
    }

    /**
     * Prepare a response for the given exception.
     *
     * @param Request   $request http request
     * @param Throwable $e       exception
     * 
     * @return BaseResponse http response
     */
    protected function prepareResponse($request, Throwable $e): BaseResponse
    {
        if (!$this->isHttpException($e)) {
            if (is_debug()) {
                return $this->toDefaultResponse($e);
            }

            $e = new HttpException(500, $e->getMessage());
        }

        return $this->renderHttpException($e);
    }

    /**
     * Get the response content for the given exception.
     *
     * @param Throwable $e exception
     * 
     * @return string exception content
     */
    protected function renderExceptionContent(Throwable $e): string
    {
        try {
            return is_debug() && class_exists(Whoops::class)
                ? $this->renderWhoopsException($e)
                : $this->renderDefaultException($e, is_debug());
        } catch (Exception $e) {
            return $this->renderDefaultException($e, is_debug());
        }
    }

    /**
     * Render a "Whoops" exception.
     *
     * @param Throwable $e exception
     * 
     * @return string rendered exception
     */
    protected function renderWhoopsException(Throwable $e): string
    {
        $whoops = new Whoops;

        $whoops->appendHandler((new WhoopsHandler)->create());
        $whoops->writeToOutput(false);
        $whoops->allowQuit(false);

        return $whoops->handleException($e);
    }

    /**
     * Render a default exception.
     *
     * @param Throwable $e     exception
     * @param bool      $debug debug mode flag 
     * 
     * @return string rendered exception
     */
    protected function renderDefaultException(Throwable $e, bool $debug): string
    {
        return (new HtmlErrorRenderer($debug))->render($e)->getAsString();
    }

    /**
     * Render the given HttpException.
     *
     * @param HttpException $e exception
     * 
     * @return BaseResponse http response
     */
    protected function renderHttpException(HttpException $e): BaseResponse
    {
        if (view_exists($view = $this->getHttpExceptionView($e))) {
            return response()->view(
                $view,
                [
                    'message' => $e->getMessage(),
                    'status'  => $e->getStatusCode(),
                    'version' => getenv('APP_VERSION')
                ],
                $e->getStatusCode(),
                $e->getHeaders()
            );
        }

        return $this->toDefaultResponse($e);
    }

    /**
     * Get the view used to render HTTP exceptions.
     *
     * @param HttpException $e exception
     * 
     * @return string exception view
     */
    protected function getHttpExceptionView(HttpException $e): string
    {
        return "framework/error/{$e->getStatusCode()}";
    }

    /**
     * Map the given exception into a HTTP response.
     *
     * @param Throwable $e exception
     * 
     * @return Response http response
     */
    protected function toDefaultResponse(Throwable $e): Response
    {
        $response = new Response(
            $this->renderExceptionContent($e),
            $this->isHttpException($e) ? $e->getStatusCode() : 500,
            $this->isHttpException($e) ? $e->getHeaders() : []
        );

        return $response->setException($e);
    }

    /**
     * Prepare a JSON response for the given exception.
     *
     * @param Request   $request http request
     * @param Throwable $e       exception
     * 
     * @return JsonResponse http response
     */
    protected function prepareJsonResponse($request, Throwable $e): JsonResponse
    {
        return new JsonResponse(
            $this->convertExceptionToArray($e),
            $this->isHttpException($e) ? $e->getStatusCode() : 500,
            $this->isHttpException($e) ? $e->getHeaders() : [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Convert the given exception to an array.
     *
     * @param Throwable $e exception
     * 
     * @return array exception data
     */
    protected function convertExceptionToArray(Throwable $e): array
    {
        $trace = array_map(function ($trace) {
            return Arr::except($trace, ['args']);
        }, $e->getTrace());

        return is_debug() ? [
            'message'   => $e->getMessage(),
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $trace,
        ] : [
            'message' => $this->isHttpException($e) ? $e->getMessage() : 'Server Error'
        ];
    }

    /**
     * Determine if the given exception is an HTTP exception.
     *
     * @param Throwable $e exception
     * 
     * @return bool whether it is a http exception or not
     */
    protected function isHttpException(Throwable $e): bool
    {
        return $e instanceof HttpException;
    }

    /**
     * Get the exception message to log.
     * 
     * @param Throwable $e exception
     * 
     * @return string message
     */
    protected function getExceptionMessageToLog(Throwable $e): string
    {
        if ($e instanceof DuplicateInsertException) {
            // The actual database exception is stored within the class
            return $e->getException()->getMessage();
        }

        return $e->getMessage();
    }
}
