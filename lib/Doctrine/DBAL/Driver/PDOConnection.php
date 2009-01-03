<?php
/**
 * PDO implementation of the driver Connection interface.
 * Used by all PDO-based drivers.
 *
 * @since 2.0
 */
class Doctrine_DBAL_Driver_PDOConnection extends PDO implements Doctrine_DBAL_Driver_Connection
{
    public function __construct($dsn, $user = null, $password = null, array $options = null)
    {
        parent::__construct($dsn, $user, $password, $options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('Doctrine_DBAL_Driver_PDOStatement', array()));
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    }
}
