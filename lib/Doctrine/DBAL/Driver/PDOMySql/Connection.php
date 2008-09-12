<?php

class Doctrine_DBAL_Driver_PDOMySql_Connection extends PDO implements Doctrine_DBAL_Connection
{
    private $_isolationLevel = Doctrine_DBAL_Connection::TRANSACTION_READ_COMMITTED;
    
    /**
     * Set the transacton isolation level.
     *
     * @param   string  standard isolation level
     *                  READ UNCOMMITTED (allows dirty reads)
     *                  READ COMMITTED (prevents dirty reads)
     *                  REPEATABLE READ (prevents nonrepeatable reads)
     *                  SERIALIZABLE (prevents phantom reads)
     *
     * @throws Doctrine_Transaction_Exception           if using unknown isolation level
     * @throws PDOException                             if something fails at the PDO level
     * @return void
     */
    public function setTransactionIsolation($level)
    {
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
        
        return $this->exec('SET SESSION TRANSACTION ISOLATION LEVEL ' . $sql);
    }

    /**
     * getTransactionIsolation
     *
     * @return string               returns the current session transaction isolation level
     */
    public function getTransactionIsolation()
    {
        return $this->_isolationLevel;
    }
    
}

?>