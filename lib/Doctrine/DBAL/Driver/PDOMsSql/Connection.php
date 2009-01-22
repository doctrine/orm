<?php

namespace Doctrine\DBAL\Driver\PDOMsSql;

/**
 * MsSql Connection implementation.
 *
 * @since 2.0
 */
class Connection extends PDO implements \Doctrine\DBAL\Driver\Connection
{
    /**
     * Performs the rollback.
     * 
     * @override
     */
    public function rollback()
    {
        $this->exec('ROLLBACK TRANSACTION');
    }
    
    /**
     * Performs the commit.
     * 
     * @override
     */
    public function commit()
    {
        $this->exec('COMMIT TRANSACTION');
    }
    
    /**
     * Begins a database transaction.
     * 
     * @override
     */
    public function beginTransaction()
    {
        $this->exec('BEGIN TRANSACTION');
    }
}

