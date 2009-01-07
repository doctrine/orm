<?php

#namespace Doctrine\Tests\Mocks;

class Doctrine_DriverConnectionMock implements Doctrine_DBAL_Driver_Connection
{
    public function prepare($prepareString) {}
    public function query() {}
    public function quote($input) {}
    public function exec($statement) {}
    public function lastInsertId() {}
    public function beginTransaction() {}
    public function commit() {}
    public function rollBack() {}
    public function errorCode() {}
    public function errorInfo() {}
}

