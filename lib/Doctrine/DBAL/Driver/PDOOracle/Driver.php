<?php

#namespace Doctrine::DBAL::Driver::PDOOracle;

#use Doctrine::DBAL::Driver;

class Doctrine_DBAL_Driver_PDOOracle_Driver implements Doctrine_DBAL_Driver
{
    
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new Doctrine_DBAL_Driver_PDOConnection(
                $this->_constructPdoDsn($params),
                $username,
                $password,
                $driverOptions);
    }
    
    /**
     * Constructs the Oracle PDO DSN.
     *
     * @return string  The DSN.
     */
    private function _constructPdoDsn(array $params)
    {
        //TODO
    }
    
    public function getDatabasePlatform()
    {
        return new Doctrine_DatabasePlatform_OraclePlatform();
    }
    
    public function getSchemaManager(Doctrine_Connection $conn)
    {
        return new Doctrine_Schema_OracleSchemaManager($conn);
    }
    
}

?>