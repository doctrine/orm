<?php

/**
 * Connection interface.
 * Drivers must implement this interface.
 *
 * This includes the full PDO interface as well as custom extensions of
 * Doctrine's DBAL.
 * 
 * @since 2.0
 */
interface Doctrine_DBAL_Connection
{
    const TRANSACTION_READ_UNCOMMITTED = 1;
    const TRANSACTION_READ_COMMITTED = 2;
    const TRANSACTION_REPEATABLE_READ = 3;
    const TRANSACTION_SERIALIZABLE = 4;
    
    /* PDO interface */
    public function prepare($prepareString);
    public function query($queryString);
    public function quote($input);
    public function exec($statement);
    public function lastInsertId();
    public function beginTransaction();
    public function commit();
    public function rollBack();
    public function errorCode();
    public function errorInfo();
    
    /* Doctrine DBAL extensions */
    public function setTransactionIsolation($level);
    public function getTransactionIsolation();
}

?>