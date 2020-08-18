<?php

namespace Example\Tests\Traits;

use Exception;

/**
 * Trait for helpful database testing methods.
 */
trait DatabaseTrait
{
    /**
     * Get the database class.
     * 
     * @return Database database
     */
    protected function getDatabase()
    {
        return container('Mini\Database\Database');
    }

    /**
     * Change the DI container database to a mocked version.
     * 
     * @param mixed $database mocked database
     *
     * @return void
     */
    protected function setMockDatabase($database): void
    {
        if ($database instanceof \Closure) {
            $database = $database();
        }

        container()->setService('Mini\Database\Database', $database);
    }

    /**
     * Truncate a database table.
     * 
     * @param string $table table name
     * 
     * @return void
     */
    protected function truncateTable(string $table): void
    {
        container('Mini\Database\Database')->truncate($table);
    }

    /**
     * Truncate a list of database tables.
     * 
     * @param array $tables table names
     * 
     * @return void
     */
    protected function truncateTables(array $tables): void
    {
        $db = container('Mini\Database\Database');

        foreach ($tables as $table) {
            $db->truncate($table);
        }
    }

    /**
     * Load seed data into a database table.
     *
     * @param string $table    table name
     * @param array  $data     seed data
     * @param bool   $truncate flag to determine if we should truncate the table before load
     * 
     * @return void
     */
    protected function loadDatabaseData(string $table, array $data, bool $truncate = false): void
    {
        $db = container('Mini\Database\Database');

        $convertSeedDataToString = function ($rows) use ($db) {
            $config = $db::getLoadDataConfig();

            // Convert the array into a flat string with a specific field delimiter
            foreach ($rows as $rowIdx => &$row) {
                foreach ($row as $fieldIdx => &$field) {
                    if (is_string($field)) {
                        $field = '"' . $db->escape($field) . '"';
                    }
                }

                $row = implode($config['fieldDelimiter'], $row);
            }

            // Separate each row with a specific line delimiter
            return implode($config['lineDelimiterRaw'], $rows);
        };

        $f = container('Mini\File\File');

        // Generate a temporary file for loading the seed data
        $file = $f->create($convertSeedDataToString($data));

        $db->loadFile($table, $file, $truncate);

        // Cleanup
        $f->remove($file);
    }

    /**
     * Load seed file data into a database table.
     *
     * @param string $table    table name
     * @param string $file     seed file path
     * @param bool   $truncate flag to determine if we should truncate the table before load
     * 
     * @return void
     */
    protected function loadDatabaseFile(string $table, string $file, bool $truncate = true): void
    {
        container('Mini\Database\Database')->loadFile($table, $file, $truncate);
    }

    /**
     * Load sets of seed file data into the database.
     *
     * Note: similar to loadDatabaseFile, this takes an array of seed
     * data in the format:
     * 
     * [
     *   [
     *     'table' => 'my_table',
     *     'file'  => 'path/to/file'
     *   ],
     *   [
     *     'table'    => 'my_table2',
     *     'file'     => 'path/to/file2',
     *     'truncate' => false
     *   ]
     * ]
     * 
     * @param array $seeds list of seed files/tables
     * 
     * @return void
     */
    protected function loadDatabaseFiles(array $seeds): void
    {
        $db = container('Mini\Database\Database');

        foreach ($seeds as $seed) {
            $db->loadFile(
                $seed['table'],
                $seed['file'],
                // Default to truncate if not supplied
                array_exists('truncate', $seed, true)
            );
        }
    }

    /**
     * Add database seed data through an insert statement.
     *
     * Note: This is used for when you need manual insert statements like when
     * trying to insert binary data. This is a cleaner alternative to trying to
     * build out a dynamic SET statement for specific operations (UNHEX, etc).
     * 
     * @param string $table   table to insert into
     * @param array  $columns table column names
     * @param array  $data    data to insert (should match index order of columns)
     * 
     * @return void
     */
    protected function insertDatabaseData(string $table, array $columns, array $data): void
    {
        $db = $this->getDatabase();

        $values = [];

        // If wrapping the string field, make sure to escape it
        $prepareField = function ($value, $wrap = true) use ($db) {
            return $wrap ? '"' . $db->escape($value) . '"' : $value;
        };

        foreach ($data as &$row) {
            foreach ($row as &$field) {
                if (is_array($field)) {
                    // To allow for not wrapping the string field value,
                    // pass an array where the second index is false
                    $field = $prepareField($field[0], $field[1]);
                } elseif (is_string($field)) {
                    $field = $prepareField($field);
                }
            }

            $values[] = '(' . implode(',', $row) . ')';
        }

        $sql = 'INSERT INTO ' . getenv('DB_SCHEMA') . '.' . $table .
            ' (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values);

        $db->statement(['sql' => $sql]);
    }

    /**
     * Get a closure for the Mockery::mock()->withArgs() method for checking
     * if a database input argument was passed.
     *
     * This helper method prevents some additional markup in the unit test.
     *
     * Instead of:
     *
     * $database->shouldReceive('select')
     *     ->once()
     *     ->withArgs(function ($args) use ($input) {
     *         return $args['input'][0] === $input;
     *     });
     *
     * We can have:
     *
     * $database->shouldReceive('select')
     *     ->once()
     *     ->withArgs($this->withDatabaseInput($input, 0));
     *
     * Note: This is needed due to the database select/statement methods taking
     * in the inputs through an array key instead of a specific argument.
     * 
     * @param mixed    $input      input value to verify
     * @param int|null $inputIndex optional position in the inputs array where the input is
     * 
     * @return callable closure to execute
     */
    protected function withDatabaseInput($input, ?int $inputIndex = null): callable
    {
        return function ($args) use ($input, $inputIndex) {
            if (is_null($inputIndex)) {
                // An input index was not passed in, verify the entire inputs array
                return $args['inputs'] === $input;
            } else {
                return $args['inputs'][$inputIndex] === $input;
            }
        };
    }
    
    /**
     * This will format a database JSON string by converting it
     * to match with a PHP encoded JSON string.
     *
     * Note: This is to get around MySQL modifying the JSON payload
     * by adding in spaces after colons and commas.
     *
     * @param string $json database json
     * 
     * @return string formatted app json
     */
    protected function formatDatabaseJson(string $json): string
    {
        $formatters = [
            ['": "', '":"'],
            ['": {', '":{'],
            ['", "', '","']
        ];

        foreach ($formatters as $formatter) {
            $json = str_replace($formatter[0], $formatter[1], $json);
        }

        return $json;
    }
}
