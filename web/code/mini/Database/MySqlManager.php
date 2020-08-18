<?php

declare(strict_types = 1);

namespace Mini\Database;

/**
 * MySQL manager.
 */
class MySqlManager implements Database
{
    /**
     * Connection instances.
     * 
     * @var array
     */
    protected static $instances = [];

    /**
     * Setup.
     * 
     * @param array $config database config
     */
    public function __construct(array $config)
    {
        $this->setupConnections($config);
    }

    /**
     * Setup database connection instances.
     * 
     * @param array $config database config
     * 
     * @return void
     */
    protected function setupConnections(array $config): void
    {
        $createInstance = function ($socket) use ($config) {
            $connection = new MySqlConnection(array_merge($config, [
                // Default to the ini config socket if not specified (mirror of mysqli constructor)
                'socket' => ($socket ?: ini_get('mysqli.default_socket'))
            ]));

            return new MySql($connection);
        };

        if ($config['sockets']['ro']) {
            // Read-write/read-only configuration
            static::$instances['rw'] = $createInstance($config['sockets']['rw']);
            static::$instances['ro'] = $createInstance($config['sockets']['ro']);
        } else {
            // (Default) Read-write configuration with read-only referencing
            static::$instances['rw'] = $createInstance($config['sockets']['rw']);
            static::$instances['ro'] = static::$instances['rw'];
        }
    }

    /**
     * Resolve which connection to use.
     * 
     * @param string $method database method name
     * 
     * @return MySql database connection instance
     */
    protected static function resolveInstance(string $method): MySql
    {
        if (static::$instances['rw']->inTransaction()) {
            // No matter the method, if in a transaction, work on the read-write connection
            return static::$instances['rw'];
        } elseif ($method === 'select') {
            return static::$instances['ro'];
        } else {
            // Default to read-write for everything except select
            return static::$instances['rw'];
        }
    }

    /**
     * Call a method on a specific connection instance.
     *
     * @param string $method    method name
     * @param array  $arguments method arguments
     * 
     * @return mixed value
     */
    public function __call(string $method, array $arguments)
    {
        $instance = static::resolveInstance($method);

        if (in_array($method, ['connect', 'connected'])) {
            return $instance->getConnection()->{$method}(...$arguments);
        }

        return $instance->{$method}(...$arguments);
    }

    /**
     * Call a static method on a specific connection instance.
     *
     * @param string $method    method name
     * @param array  $arguments method arguments
     * 
     * @return mixed value
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $instance = static::resolveInstance($method);

        return $instance::{$method}(...$arguments);
    }
}
