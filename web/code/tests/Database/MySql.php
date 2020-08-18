<?php

declare(strict_types = 1);

namespace Example\Tests\Database;

use Exception;
use Mini\Database\MySql as BaseMySql;

/**
 * Test MySQL query logic.
 */
class MySql extends BaseMySql
{
    /**
     * Load data infile config.
     * 
     * @var array
     */
    public static $loadDataConfig = [
        'enclosedDelimiter' => '"',
        'escapedDelimiter'  => '\\\\',
        'fieldDelimiter'    => '||',
        'lineDelimiter'     => '\n',
        'lineDelimiterRaw'  => "\n"
    ];

    /**
     * Truncate a database table.
     * 
     * @param string $table table name
     * 
     * @return void
     */
    public function truncate(string $table): void
    {
        if (!is_testing()) {
            throw new Exception('Method disabled outside test environment');
        }

        $truncated = $this->query('TRUNCATE TABLE ' . getenv('DB_SCHEMA') . '.' . $table);

        if (!$truncated) {
            throw new Exception('Failed to truncate: ' . $table);
        }
    }

    /**
     * Load a database seed file.
     *
     * Note: Because we use "LOCAL" load data, duplicate key errors are transformed
     * to warnings so be careful of what you load in.
     * 
     * @param string $table    table name
     * @param string $file     file path
     * @param bool   $truncate flag to determine if we should truncate the table before load
     * 
     * @return void
     */
    public function loadFile(string $table, string $file, bool $truncate = true): void
    {
        if (!is_testing()) {
            throw new Exception('Method disabled outside test environment');
        }

        if ($truncate) {
            $this->truncate($table);
        }

        $file = $this->escape($file);
        
        $query = "LOAD DATA LOCAL INFILE '$file' INTO TABLE $table"
            . " FIELDS TERMINATED BY '" . static::$loadDataConfig['fieldDelimiter'] . "'"
            . " OPTIONALLY ENCLOSED BY '" . static::$loadDataConfig['enclosedDelimiter'] . "'"
            . " ESCAPED BY '" . static::$loadDataConfig['escapedDelimiter'] . "'"
            . " LINES TERMINATED BY \"" . static::$loadDataConfig['lineDelimiter'] . "\"";

        $loaded = $this->query($query);

        if (!$loaded) {
            throw new Exception('Failed to load table (' . $table . ') file: ' . $file);
        }
    }

    /**
     * Get the load data infile configuration.
     *
     * @return array config
     */
    public static function getLoadDataConfig(): array
    {
        return static::$loadDataConfig;
    }
}
