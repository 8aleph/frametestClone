<?php

declare(strict_types = 1);

namespace Mini;

use Exception;
use Invoker\Invoker;
use Invoker\ParameterResolver\Container\TypeHintContainerResolver;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * The application is done almost entirely with
 * dependency injection but there are a couple use
 * cases (mainly parent constructor for setting up
 * common dependencies that don't need to be set for every
 * controller and some test cases where we don't want to mock)
 * where it is nice to have the functionality of a "service locator".
 */
class Container
{
    /**
     * Dependency container instance.
     * 
     * @var \CompiledContainer|null
     */
    protected static $container = null;

    /**
     * Location of the cached container.
     *
     * @const string
     */
    const CACHED_CONTAINER_FILE = '/cache/container.php';

    /**
     * Get an instance of the container.
     * 
     * @return \CompiledContainer compiled container instance
     */
    public static function getInstance(): \CompiledContainer
    {
        // Set it up as a singleton per request
        if (static::$container === null) {
            require_once self::getCompiledContainerClass();
            
            static::$container = new class extends \CompiledContainer
            {
                /**
                 * Setup.
                 *
                 * Note: This gets around Symfony not allowing parameters on
                 *       compiled containers for whatever reason.
                 *
                 * Note: This uses the HttpFoundation version of a parameter bag
                 *       that allows for default values if a key doesn't exist
                 *       instead of having to deal with extra markup with "has".
                 */
                public function __construct()
                {
                    parent::__construct();

                    $this->parameterBag = new ParameterBag();
                }

                /**
                 * Call the given function using the given parameters.
                 *
                 * Note: The parameters can be indexed by the parameter names
                 * or not indexed (same order as the parameters).
                 * 
                 * @param callable $callable   function to call
                 * @param array    $parameters parameters to use
                 * 
                 * @return mixed callable result
                 */
                public function call($callable, array $parameters = [])
                {
                    $invoker = new Invoker(null, $this);
                    $invoker->getParameterResolver()->prependResolver(
                        new TypeHintContainerResolver($this)
                    );

                    return $invoker->call($callable, $parameters);
                }

                /**
                 * Set/override an already set service. This gets around Symfony
                 * not allowing overriding an already created service (which we want)
                 * for the ability to mock an object.
                 * 
                 * @param string $id      service id
                 * @param mixed  $service service class objct
                 *
                 * @return void
                 */
                public function setService(string $id, $service): void
                {
                    $this->services[$id] = $service;
                }
            };
        }
        
        return static::$container;
    }

    /**
     * Get the compiled container class file.
     *
     * Note: this will handle compiling the container if the file
     * does not exist or if we are in debug mode.
     * 
     * @return string $compiledContainer path to compiled container class file
     */
    protected static function getCompiledContainerClass(): string
    {
        $compiledContainer = dirname(__DIR__) . self::CACHED_CONTAINER_FILE;

        $containerCache = new ConfigCache($compiledContainer, is_debug());

        // If the file doesn't exist, has changed or we are in debug mode,
        // then compile the container and save it to our project directory 
        if (!$containerCache->isFresh()) {
            $builder = new ContainerBuilder();

            // Load the service definitions
            $loader = new PhpFileLoader($builder, new FileLocator(dirname(__DIR__) . '/config'));
            $loader->load('services.php');

            $builder->compile();

            $containerCache->write(
                (new PhpDumper($builder))->dump(['class' => 'CompiledContainer']),
                $builder->getResources()
            );
        }

        return $compiledContainer;
    }

    /**
     * Prevent creating a new instance.
     */
    protected function __construct()
    {
        //
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone()
    {
        //
    }

    /**
     * Prevent unserializing of the instance.
     */
    private function __wakeup()
    {
        //
    }
}
