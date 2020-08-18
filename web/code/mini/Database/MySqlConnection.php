<?php

declare(strict_types = 1);

namespace Mini\Database;

use Exception;
use mysqli;

/**
 * MySQL connection logic.
 */
class MySqlConnection implements Database
{
    /**
     * Database config.
     * 
     * @var array
     */
    protected $config = [];

    /**
     * Active database connection.
     * 
     * @var mysqli|null
     */
    protected $connection = null;

    /**
     * Flag to check if we are connected to a database.
     * 
     * @var bool
     */
    protected $connected = false;

    /**
     * Setup.
     * 
     * @param array $config database config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Check if we are connected to the database.
     * 
     * @return bool whether we are connected or not
     */
    public function connected(): bool
    {
        return $this->connected;
    }

    /**
     * Open a new connection to the server.
     * 
     * @return void
     * 
     * @throws Exception if the connection could not be established
     * @throws Exception if failing to set the db charset
     */
    public function connect(): void
    {
        if ($this->connected) {
            return;
        }

        $attempts = 0;

        // Attempt a connection to the database. Incase a router/cluster is setup,
        // we will attempt 3 connectionns to the database with a one second delay
        // pause if we fail to connect
        do {
            $this->connection = new mysqli(
                gethostbyname($this->config['host']),
                $this->config['user'],
                $this->config['pass'],
                $this->config['schema'],
                (int) $this->config['port'],
                $this->config['socket']
            );

            $attempts++;

            if ($this->connection->connect_errno) {
                // If we failed to connect, wait a second before re-attempting
                sleep(1);
            }
        } while ($this->connection->connect_errno && $attempts < 100);

        // Double check we didn't get a connection error
        if ($this->connection->connect_errno) {
            throw new Exception('Failed to connect');
        }

        $this->verifyCharset();

        $this->connected = true;
    }

    /**
     * Verify the connection charset.
     * 
     * @return void
     * 
     * @throws Exception if failing to set the db charset
     */
    protected function verifyCharset(): void
    {
        // Check the connection character set against the environment config
        if ($this->config['charset'] !== $this->connection->character_set_name()) {
            // It is not the same, attempt to reset it
            if (!$this->connection->set_charset($this->config['charset'])) {
                throw new Exception('Error setting database charset: ' . $this->config['charset']);
            }
        }
    }

    /**
     * Get a member variable from the active connection.
     *
     * @param string $name connection member variable
     * 
     * @return mixed value
     */
    public function __get(string $name)
    {
        return $this->connection->$name;
    }

    /**
     * Call a method on the active connection.
     *
     * @param string $method    method name
     * @param array  $arguments method arguments
     * 
     * @return mixed value
     */
    public function __call(string $method, array $arguments)
    {
        if (!$this->connected()) {
            $this->connect();
        }

        return $this->connection->{$method}(...$arguments);
    }
}
