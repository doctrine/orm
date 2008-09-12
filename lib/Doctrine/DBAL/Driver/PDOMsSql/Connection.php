<?php

#namespace Doctrine::DBAL::Driver::PDOMsSql;

/**
 * MsSql Connection implementation.
 *
 * @since 2.0
 */
class Doctrine_DBAL_Driver_PDOMsSql_Connection extends PDO implements Doctrine_DBAL_Connection
{
    private $_isolationLevel = Doctrine_DBAL_Connection::TRANSACTION_READ_COMMITTED;
    
    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level (SQL-92)
     *      portable modes:
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     *
     * @link http://msdn2.microsoft.com/en-us/library/ms173763.aspx
     * @throws PDOException                         if something fails at the PDO level
     * @throws Doctrine_Transaction_Exception       if using unknown isolation level or unknown wait option
     * @return void
     * @override
     */
    public function setTransactionIsolation($level, $options = array()) {
        $sql = "";
        switch ($level) {
            case Doctrine_DBAL_Connection::TRANSACTION_READ_UNCOMMITTED:
                $sql = 'READ UNCOMMITTED';
                break;
            case Doctrine_DBAL_Connection::TRANSACTION_READ_COMMITTED:
                $sql = 'READ COMMITTED';
                break;
            case Doctrine_DBAL_Connection::TRANSACTION_REPEATABLE_READ:
                $sql = 'REPEATABLE READ';
                break;
            case Doctrine_DBAL_Connection::TRANSACTION_SERIALIZABLE:
                $sql = 'SERIALIZABLE';
                break;
            default:
                throw new Doctrine_Transaction_Exception('isolation level is not supported: ' . $isolation);
        }

        $this->_isolationLevel = $level;
        $this->exec('SET TRANSACTION ISOLATION LEVEL ' . $sql);
    }
    
    public function getTransactionIsolation()
    {
        return $this->_isolationLevel;
    }
    
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

?>