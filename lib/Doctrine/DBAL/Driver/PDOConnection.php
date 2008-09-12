<?php

class Doctrine_DBAL_Driver_PDOConnection extends PDO implements Doctrine_DBAL_Driver_Connection
{
    public function __construct($dsn, $user = null, $password = null, array $options = null)
    {
        parent::__construct($dsn, $user, $password, $options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('Doctrine_DBAL_Driver_PDOStatement', array()));
    }
}

?>