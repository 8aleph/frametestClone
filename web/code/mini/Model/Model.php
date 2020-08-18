<?php

declare(strict_types = 1);

namespace Mini\Model;

/**
 * Wrapper for database models.
 */
class Model
{
    /**
     * Database object.
     * 
     * @var Mini\Database\Database|null
     */
    protected $db = null;

    /**
     * Setup common objects that the models can use.
     */
    public function __construct()
    {
        $this->db = container('Mini\Database\Database');
    }

    /**
     * Start a transaction.
     * 
     * Note: this would be for some class aside from the model that is starting
     * and commiting the transaction since it can't directly access the db object.
     *
     * @param string $isolationLevel optional isolation level override for the transaction
     * 
     * @return void
     */
    public function startTransaction(string $isolationLevel = 'SERIALIZABLE'): void
    {
        $this->db->startTransaction($isolationLevel);
    }

    /**
     * Commit a transaction.
     * 
     * Note: this would be for some class aside from the model that is starting
     * and commiting the transaction since it can't directly access the db object
     *
     * @return void
     */
    public function commitTransaction(): void
    {
        $this->db->commitTransaction();
    }

    /**
     * Rollback a transaction.
     * 
     * Note: this would be for some class aside from the model that is rolling back
     * the transaction since it can't directly access the db object
     *
     * @return void
     */
    public function rollbackTransaction(): void
    {
        $this->db->rollbackTransaction();
    }

    /**
     * Check if we are currently inside a transaction.
     * 
     * @return bool flag if we are in a transaction or not
     */
    public function inTransaction(): bool
    {
        return $this->db->inTransaction();
    }
}
