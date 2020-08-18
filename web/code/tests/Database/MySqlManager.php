<?php

declare(strict_types = 1);

namespace Example\Tests\Database;

use Example\Tests\Database\MySql;
use Mini\Database\MySqlConnection;
use Mini\Database\MySqlManager as BaseMySqlManager;

/**
 * Test MySQL manager.
 */
class MySqlManager extends BaseMySqlManager
{
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
     * Setup database instances.
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
}
