<?php

declare(strict_types = 1);

namespace Mini;

use Mini\Exception\ExceptionBootstrap;
use Mini\Exception\ExceptionHandler;
use Mini\Http\Request;
use Mini\Http\Router;
use Mini\Log\Log;
use Mini\Util\Json;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Throwable;

/**
 * Front controller to application.
 */
class App
{
    /**
     * Dependency injector.
     * 
     * @var Mini\Container|null
     */
    protected $container = null;

    /**
     * Process the request through the router and send back a response.
     *
     * @param Request $request http request
     * 
     * @return BaseResponse $response http response
     */
    public function run(Request $request): BaseResponse
    {
        $this->initialize($request);
        
        try {
            $response = $this->getRouter()->dispatch($request);
        } catch (Throwable $e) {
            $this->logException($e);

            $response = $this->renderException($request, $e);
        }

        return $response;
    }

    /**
     * Log the exception.
     * 
     * @param Throwable $e exception
     * 
     * @return void
     */
    protected function logException(Throwable $e): void
    {
        $this->container->get(ExceptionHandler::class)->log($e);
    }

    /**
     * Render the exception.
     * 
     * @param Request   $request http request
     * @param Throwable $e       exception
     * 
     * @return Response http response
     */
    protected function renderException(Request $request, Throwable $e): BaseResponse
    {
        return $this->container->get(ExceptionHandler::class)->render($request, $e);
    }

    /**
     * Get the application request router.
     * 
     * @return Router $router application router
     */
    protected function getRouter(): Router
    {
        $router = $this->container->get(Router::class);
        $router->setContainer($this->container);

        return $router;
    }

    /**
     * Initialize the application.
     *
     * @param Request $request http request
     * 
     * @return void
     */
    protected function initialize(Request $request): void
    {
        $this->container = container();

        // Attach the request to the container so other methods can access it
        $this->container->setParameter('request', $request);

        // Setup exceptions
        $this->container->get(ExceptionBootstrap::class)->boot($this->container);
    }
}
