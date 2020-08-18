<?php

declare(strict_types = 1);

namespace Example\Tests;

use Example\Tests\ApiMock;
use Example\Tests\Database\MySqlManager;
use Example\Tests\Traits\DatabaseTrait;
use Mini\Util\Curl;
use Mini\Util\DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Base test class for testing protected/private methods
 * or taking advantage of the dependency injector.
 */
class BaseCase extends TestCase
{
    use DatabaseTrait;
    use MockeryPHPUnitIntegration;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->setupTestDatabase();
    }

    /**
     * Clean up the test environment.
     * 
     * @return void
     */
    protected function tearDown(): void
    {
        DateTime::setTestNow();
        Mockery::close();
    }

    /**
     * Get the current time.
     * 
     * @param string $format time format
     * 
     * @return string current time in specified format
     */
    protected function now(string $format = 'Y-m-d H:i:s'): string
    {
        return DateTime::now()->format($format);
    }

    /**
     * Get a non mocked class injected with its dependencies.
     * 
     * @param string $class full namespace of class
     * 
     * @return Object class object
     */
    protected function getClass(string $class)
    {
        return container($class);
    }

    /**
     * Get a new instance of the curl wrapper.
     * 
     * @return Curl curl object
     */
    protected function curl(): Curl
    {
        return container('Mini\Util\Curl');
    }

    /**
     * Get the response that would be sent back to the client.
     *
     * @return mixed response data
     */
    protected function getResponse()
    {
        return container('Mini\Http\Response')->getContent();
    }
    
    /**
     * Invoke the given function/class/method using the given parameters.
     *
     * Note: The parameters can be indexed by the parameter names
     * or not indexed (same order as the parameters).
     * 
     * @param callable $callable   function to call
     * @param array    $parameters parameters to use
     * 
     * @return mixed callable result
     */
    protected function call($callable, array $parameters = [])
    {
        return container()->call($callable, $parameters);
    }

    /**
     * Get a mock version of a class object.
     *
     * @param string $class class to mock
     * 
     * @return mixed mocked object
     */
    protected function getMock(string $class)
    {
        return Mockery::mock($class);
    }

    /**
     * Change the DI container service to a mocked version.
     * 
     * @param string $name    name of the service (this is usually the namespace)
     * @param mixed  $service mocked service/closure to setup the service
     *
     * @return void
     */
    protected function setMock(string $name, $service): void
    {
        if ($service instanceof \Closure) {
            $service = $service();
        }
        
        container()->setService($name, $service);
    }

    /**
     * Set a property of an object.
     * 
     * @param string $class    class of property
     * @param string $property property to change
     * @param mixed  $value    value to set
     *
     * @return void
     */
    protected function setProperty(string $class, string $property, $value): void
    {
        if (is_string($class)) {
            $class = container($class);
        }

        $refClass = new \ReflectionClass($class);

        $reflectionProperty = $refClass->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($class, $value);
    }

    /**
     * Run a protected/private method of a class through reflection. Either
     * pass in the full namespace or an instance of the class
     * (for when you need to set constructor args yourself).
     * 
     * @param mixed  $class  class name or object
     * @param string $method method to call
     * @param array  $args   method parameters
     * 
     * @return mixed method return value
     */
    protected function invokeMethod($class, string $method, array $args)
    {
        $refMethod = $this->getMethod($class, $method);

        if (is_string($class)) {
            $class = container($class);
        }

        return $refMethod->invokeArgs($class, $args);
    }

    /**
     * Used by `execute` to get a reflection method.
     * 
     * @param mixed  $class  class object or string
     * @param string $method method to initialize
     * 
     * @return ReflectionMethod reflection method
     */
    protected function getMethod($class, string $method): \ReflectionMethod
    {
        $refClass = new \ReflectionClass($class);

        $refMethod = $refClass->getMethod($method);
        $refMethod->setAccessible(true);

        return $refMethod;
    }

    /**
     * Setup the test database wrapper.
     * 
     * @return void
     */
    protected function setupTestDatabase(): void
    {
        $manager = new MySqlManager([
            'host'    => getenv('DB_HOST'),
            'port'    => getenv('DB_PORT'),
            'user'    => getenv('DB_USER'),
            'pass'    => getenv('DB_PASS'),
            'schema'  => getenv('DB_SCHEMA'),
            'charset' => getenv('DB_CHARSET'),
            'sockets' => [
                'rw' => null,
                'ro' => null
            ]
        ]);

        // Use the test database wrapper which extends the base with
        // testing utilities like truncate() and loadData()
        container()->setService('Mini\Database\Database', $manager);
    }
}
