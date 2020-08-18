<?php

declare(strict_types = 1);

namespace Mini\Database;

use Exception;
use Mini\Database\Exception\DuplicateInsertException;
use Throwable;

/**
 * MySQL database logic.
 */
class MySql implements Database
{
    /**
     * Active database connection.
     * 
     * @var Mini\Database\MySqlConnection|null
     */
    protected $connection = null;

    /**
     * Affected rows.
     * 
     * @var int
     */
    protected $affected = 0;

    /**
     * SQL statement handle.
     * 
     * @var object|null
     */
    protected $stmt = null;

    /**
     * Transaction flag.
     * 
     * @var bool
     */
    protected $transaction = false;

    /**
     * Setup.
     * 
     * @param MySqlConnection $connection database connection
     */
    public function __construct(MySqlConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Execute a query.
     * 
     * @param string $query query to execute
     * 
     * @return mixed $results result set
     * 
     * @throws Exception if the query fails to execute
     */
    public function query(string $query)
    {
        $results = $this->connection->query($query);

        if (!$results) {
            if ($this->transaction) {
                $this->rollbackTransaction();
            }

            throw new Exception('Failed to run the query');
        }

        return $results;
    }

    /**
     * Execute a select query.
     * 
     * @param array $args query parameters
     * 
     * @return array $results result set
     * 
     * @throws Exception if the sql is empty
     * @throws Exception if the select query fails
     * @throws Exception if more than one row was returned when specified for only one
     * @throws Exception if no rows are found and configured to find rows
     */
    public function select(array $args): array
    {
        $results = [];
        $rows    = false;

        $config = [
            'sql'         => false,
            'inputs'      => false,
            'title'       => false,
            'noRowsOk'    => true,
            'assocResult' => true,
            'singleRow'   => true
        ];

        // Override
        foreach ($args as $key => $value) {
            $config[$key] = $value;
        }

        if (!$config['sql']) {
            throw new Exception($this->getQueryDescription($config) . 'Failed to find sql');
        }

        try {
            $rows = $this->prepareExecute($config);

            $results = $config['assocResult'] ? $this->getResults() : $this->getResults(false);
        } catch (Throwable $e) {
            $this->throwDatabaseException($e, $config);
        } finally {
            $this->close();
        }

        // Validate the select to save the developer from asserting
        if ($rows && $config['singleRow']) {
            if ($rows === 1) {
                // Save an extra step and/or cleanup markup (i.e. $results['myId'] vs $results[0]['myId'])
                $results = $results[0];
            } else {
                throw new Exception($this->getQueryDescription($config) . 'Failed to find single row');
            }
        } elseif (!$rows && !$config['noRowsOk']) {
            throw new Exception($this->getQueryDescription($config) . 'Failed to find rows');
        }

        return $results;
    }

    /**
     * Execute a query. The type specified determines what is returned. Insert
     * queries will get the insert ID, whereas editing will get rows affected.
     * 
     * @param array $args query parameters
     * 
     * @return int $results insert ID (insert) or row count (update/delete)
     *
     * @throws Exception if the sql is empty
     * @throws Exception if the statement query fails
     */
    public function statement(array $args): int
    {
        $results = false;

        $config = [
            'sql'    => false,
            'inputs' => false,
            'title'  => false
        ];

        // Override
        foreach ($args as $key => $value) {
            $config[$key] = $value;
        }

        if (!$config['sql']) {
            throw new Exception('Failed to find sql');
        }

        try {
            $results = $this->prepareExecute($config);

            if ($this->isInsertStatement($config)) {
                $results = $this->getInsertId();
            }
        } catch (Throwable $e) {
            if ($this->transaction) {
                $this->rollbackTransaction();
            }

            if ($this->isDuplicateInsertError($e)) {
                throw new DuplicateInsertException($e, $config);
            }

            $this->throwDatabaseException($e, $config);
        } finally {
            $this->close();
        }

        return $results;
    }

    /**
     * Check that a query affected a single row.
     * 
     * @param int  $affected    optional number of affected rows to expect
     * @param bool $throwOnFail optional throw an exception if the validation fails
     * 
     * @return bool $validated whether a single row was affected or not
     * 
     * @throws Exception if specified and no single row affected
     */
    public function validateAffected(int $affected = 1, bool $throwOnFail = true): bool
    {
        $validated = $affected === $this->getAffected();

        if ($throwOnFail && !$validated) {
            throw new Exception(
                'Failed to validate affected row(s). Affected: ' . $this->getAffected() . ', Expected: ' . $affected
            );
        }

        return $validated;
    }

    /**
     * Get a prepared parameter string based off the number of values that
     * will get binded into the query.
     * 
     * @param array $data the values that will be binded into the query
     * 
     * @return string prepared parameter string
     */
    public function getPreparedParams(array $data): string
    {
        return rtrim(str_repeat('?,', count($data)), ',');
    }

    /**
     * Clean up special characters.
     * 
     * @param string $value value to escape
     * 
     * @return string escaped value
     */
    public function escape(string $value): string
    {
        return $this->connection->real_escape_string($value);
    }

    /**
     * Get the generated ID from the last query statement.
     * 
     * @return int auto-increment column ID
     */
    public function getInsertId(): int
    {
        return $this->stmt->insert_id;
    }

    /**
     * Get the number of rows from the last query statement.
     * 
     * @return int count of rows
     */
    public function getAffected(): int
    {
        return $this->affected;
    }

    /**
     * Start a transaction (grouping of queries).
     *
     * Note: By default we use serializable but it can be overriden.
     * 
     * @param string $isolationLevel optional isolation level override for the transaction
     * 
     * @return void
     */
    public function startTransaction(string $isolationLevel = 'SERIALIZABLE'): void
    {
        if (!$this->transaction) {
            $this->connection->query("SET SESSION TRANSACTION ISOLATION LEVEL $isolationLevel;");
            $this->connection->query("START TRANSACTION;");

            $this->transaction = true;
        }
    }

    /**
     * Complete a transaction (grouping of queries).
     * 
     * @return void
     * 
     * @throws Exception if not within a transaction
     */
    public function commitTransaction(): void
    {
        if (!$this->transaction) {
            throw new Exception('Not in transaction');
        }

        $this->connection->query("COMMIT;");
        $this->transaction = false;
    }

    /**
     * Void out a transaction (grouping of queries). This will reset the data
     * back to how it was before any queries occured.
     * 
     * @return void
     * 
     * @throws Exception if not within a transaction
     */
    public function rollbackTransaction(): void
    {
        if (!$this->transaction) {
            throw new Exception('Not in transaction');
        }

        $this->connection->query("ROLLBACK;");
        $this->transaction = false;
    }

    /**
     * Check if we are currently inside a transaction.
     * 
     * @return bool flag if we are in a transaction or not
     */
    public function inTransaction(): bool
    {
        return $this->transaction;
    }

    /**
     * Fetch the result set from the last query statement.
     * 
     * @param bool $assoc optional flag to change result return type
     * 
     * @return array $results result set
     */
    protected function getResults(bool $assoc = true): array
    {
        $fields = $results = [];

        $stmtResultMeta = $this->stmt->result_metadata();
        
        while ($field = $stmtResultMeta->fetch_field()) {
            $fieldName  = $field->name;
            $$fieldName = null;

            $fields[$fieldName] = &$$fieldName;
        }
        
        call_user_func_array([$this->stmt, 'bind_result'], $fields);

        $numOfRows = $this->getStatementNumRows();
        for ($i = 0; $i < $numOfRows; $i++) {
            $this->stmt->fetch();

            foreach ($fields as $key => $fieldValue) {
                if ($assoc) {
                    $results[$i][$key] = $fieldValue;
                } else {
                    $results[$i][] = $fieldValue;
                }
            }
        }
        
        return $results;
    }

    /**
     * Prepare a query, bind (possibly), and execute.
     * 
     * @param array $config query config
     * 
     * @return int affected/num rows
     */
    protected function prepareExecute(array $config): int
    {
        $this->prepare($config['sql']);

        if ($config['inputs']) {
            $this->bind($config['inputs']);
        }

        return $this->execute();
    }

    /**
     * Prepare a query and create statement.
     * 
     * @param string $sql query to prepare
     * 
     * @return void
     * 
     * @throws Exception if the query could not be prepared
     */
    protected function prepare(string $sql): void
    {
        if (!$this->stmt = $this->connection->prepare($sql)) {
            throw new Exception('Failed to prepare query :: ' . $this->connection->error);
        }
    }

    /**
     * Execute a query and return rows. By default we store the result and 
     * return row count versus affected rows.
     * 
     * @return int row count
     * 
     * @throws Exception if the query fails to execute
     * @throws Exception if no rows were found
     */
    protected function execute(): int
    {
        if (!$this->stmt->execute()) {
            throw new Exception('Failed to execute query :: ' . $this->stmt->error);
        }

        if (!$this->stmt->store_result()) {
            throw new Exception('Failed to get rows from execute');
        }

        return $this->affected = $this->getStatementAffectedRows();
    }

    /**
     * Bind values to a prepared statement.
     * 
     * @param array $parameters types of values to bind
     * 
     * @return void
     * 
     * @throws Exception if no bind parameters were specified
     * @throws Exception if the bind parameter is of an unknown type
     * @throws Exception if the parameter could not be bound
     */
    protected function bind(array $parameters): void
    {
        if (!count($parameters)) {
            throw new Exception('Failed to find bind parameters');
        }

        $types = '';
        foreach ($parameters as $parameter) {
            if (is_int($parameter)) {
                $types .= 'i';
            } elseif (is_float($parameter)) {
                $types .= 'd';
            } elseif (is_string($parameter)) {
                $types .= 's';
            } elseif (is_null($parameter)) {
                $types .= 's';
            } elseif (is_bool($parameter)) {
                $types .= 'b';
            } else {
                throw new Exception('Bind parameter type unknown');
            }
        }

        $binds[] = $types;

        // Variables to bind
        for ($i = 0; $i < count($parameters); $i++) {
            $bind  = 'bind' . $i;
            $$bind = $parameters[$i];

            $binds[] = &$$bind;
        }

        if (!call_user_func_array([$this->stmt, 'bind_param'], $binds)) {
            throw new Exception('Failed to bind parameter');
        }
    }

    /**
     * Close the statement connection.
     * 
     * @return void
     */
    protected function close(): void
    {
        if ($this->stmt) {
            $this->stmt->close();
        }
    }

    /**
     * Get the number of affected rows from the last query statement.
     * 
     * @return int affected rows
     */
    protected function getStatementAffectedRows(): int
    {
        return $this->stmt->affected_rows;
    }

    /**
     * Get the number of rows from the last query statement.
     * 
     * @return int number of rows
     */
    protected function getStatementNumRows(): int
    {
        return $this->stmt->num_rows;
    }

    /**
     * Check if this query is an insert statement.
     * 
     * @param array $config query config
     * 
     * @return bool whether it is insert
     */
    protected function isInsertStatement(array $config): bool
    {
        return strpos($config['sql'], 'INSERT INTO') !== false;
    }

    /**
     * Check if the error is related to a database duplicate key error.
     *
     * @param Throwable $e exception
     * 
     * @return bool whether the error is related to duplicate key
     */
    protected function isDuplicateInsertError(Throwable $e): bool
    {
        return strpos($e->getMessage(), 'Duplicate') !== false;
    }

    /**
     * Get the description for a query.
     *
     * @param array $config query config
     * 
     * @return string query description
     */
    protected function getQueryDescription(array $config): string
    {
        return $config['title'] ? $config['title'] . ' :: ' : '';
    }

    /**
     * Build a database error message.
     * 
     * @param Throwable $e      error object
     * @param array     $config query config
     *
     * @return void
     * 
     * @throws Exception database exception
     */
    protected function throwDatabaseException(Throwable $e, array $config): void
    {
        $description = $this->getQueryDescription($config);
        
        $errno = ($this->stmt && $this->stmt->errno) ? $this->stmt->errno : $this->connection->errno;

        throw new Exception($description . '[' . $errno . '] :: ' . $e->getMessage());
    }

    /**
     * Get the active connection.
     * 
     * @return MySqlConnection connection
     */
    public function getConnection(): MySqlConnection
    {
        return $this->connection;
    }
}
