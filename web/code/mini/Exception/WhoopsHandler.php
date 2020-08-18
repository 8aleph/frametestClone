<?php

namespace Mini\Exception;

use Whoops\Handler\PrettyPageHandler;

/**
 * Whoops error handler.
 */
class WhoopsHandler
{
    /**
     * Keys to remove from debug output.
     * 
     * @var array
     */
    protected static $blacklist = [
        '_ENV' => [],
        '_SERVER' => [
            'MASTER_TOKEN',
            'DB_HOST',
            'DB_PORT',
            'DB_USER',
            'DB_PASS',
            'DB_SCHEMA',
            'DB_CHARSET'
        ],
        '_POST' => [
            'password'
        ]
    ];

    /**
     * Create a new Whoops error handler for debugging.
     *
     * @return PrettyPageHandler $handler handler
     */
    public function create(): PrettyPageHandler
    {
        $handler = new PrettyPageHandler;
        $handler->handleUnconditionally(true);

        $this->registerBlacklist($handler);

        return $handler;
    }

    /**
     * Register the blacklist with the handler.
     *
     * @param PrettyPageHandler $handler handler
     * 
     * @return void
     */
    protected function registerBlacklist(PrettyPageHandler $handler): void
    {
        foreach (static::$blacklist as $key => $secrets) {
            foreach ($secrets as $secret) {
                $handler->blacklist($key, $secret);
            }
        }
    }
}
