<?php

declare(strict_types = 1);

namespace Mini\Http;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Mini\Http\JsonResponse;
use Mini\Http\Request;
use Mini\Http\Response;
use Mini\Http\Exception\MethodNotAllowedHttpException;
use Mini\Http\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\Container as BaseContainer;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

use function FastRoute\cachedDispatcher;

/**
 * Application HTTP router.
 */
class Router
{
    /**
     * Check the requested url against the allowed routes. If found,
     * the class and method associated with the route will be executed.
     * If not found, an error message and status code are returned.
     *
     * @param Request $request http request
     * 
     * @return BaseResponse http response
     */
    public function dispatch(Request $request): BaseResponse
    {
        return $this->processRoute(
            $request,
            $this->getRouteDispatcher()->dispatch($request->getMethod(), $request->getPathInfo())
        );
    }

    /**
     * Create a response instance.
     *
     * @param Request $request  http request
     * @param mixed   $response response
     * 
     * @return BaseResponse http response
     */
    public static function createResponse($request, $response): BaseResponse
    {
        if ($response instanceof ArrayObject ||
            $response instanceof JsonSerializable ||
            is_array($response)
        ) {
            $response = new JsonResponse($response);
        } elseif (!$response instanceof BaseResponse) {
            $response = new Response($response, 200, ['Content-Type' => 'text/html']);
        }

        if ($response->getStatusCode() === Response::HTTP_NOT_MODIFIED) {
            $response->setNotModified();
        }

        return $response->prepare($request);
    }

    /**
     * Process the dispatched route.
     *
     * @param Request $request http request
     * @param array   $route   dispatched route data
     * 
     * @return BaseResponse http response
     *
     * @throws NotFoundHttpException         route not found
     * @throws MethodNotAllowedHttpException method not allowed
     */
    protected function processRoute(Request $request, array $route): BaseResponse
    {
        switch ($route[0]) {
            case Dispatcher::FOUND:
                if ($route[2]) {
                    // Save the parsed route attributes to the request
                    $request->attributes->add($route[2]);
                }

                return static::createResponse(
                    $request,
                    // Execute the endpoint method
                    $this->container->get($route[1][0])->{$route[1][1]}($request)
                );

            case Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException('Not Found');

            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException('Method Not Allowed');
        }
    }

    /**
     * Define the available routes and initialize our dispatcher.
     * 
     * @return Dispatcher route dispatcher
     */
    protected function getRouteDispatcher(): Dispatcher
    {
        return cachedDispatcher(function (RouteCollector $collector) {
            $routes = $this->getRoutes();

            foreach ($routes as $route) {
                $collector->addRoute($route[0], $route[1], $route[2]);
            }
        }, [
            'cacheFile'     => dirname(dirname(__DIR__)) . '/cache/routes/route.cache',
            'cacheDisabled' => is_debug()
        ]);
    }

    /**
     * Get the allowed routes.
     * 
     * @return array list of allowed routes
     */
    protected function getRoutes(): array
    {
        return include(dirname(dirname(__DIR__)) . '/src/routes.php');
    }

    /**
     * Set the IoC container.
     * 
     * @param BaseContainer $container container
     *
     * @return self $this self for chaining
     */
    public function setContainer(BaseContainer $container): self
    {
        $this->container = $container;

        return $this;
    }
}
