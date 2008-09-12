<?php

#namespace Doctrine::DBAL::Driver::PDOPgSql;

/**
 * Driver that connects through pdo_pgsql.
 *
 * @since 2.0
 */
class Doctrine_DBAL_Driver_PDOPgSql_Driver implements Doctrine_DBAL_Driver
{
    
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new PDO($this->_constructPdoDsn($params), $username, $password, $driverOptions);
    }
    
    /**
     * Constructs the Postgres PDO DSN.
     *
     * @return string  The DSN.
     */
    private function _constructPdoDsn(array $params)
    {
        //TODO
    }
    
    public function getDatabasePlatform()
    {
        return new Doctrine_DatabasePlatform_PostgreSqlPlatform();
    }
    
    public function getSchemaManager(Doctrine_Connection $conn)
    {
        return new Doctrine_Schema_PostgreSqlSchemaManager($conn);
    }
    
}

?>