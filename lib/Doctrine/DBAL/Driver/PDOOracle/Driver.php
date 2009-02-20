<?php

namespace Doctrine\DBAL\Driver\PDOOracle;

use Doctrine\DBAL\Platforms;

class Driver implements \Doctrine\DBAL\Driver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new \Doctrine\DBAL\Driver\PDOConnection(
            $this->_constructPdoDsn($params),
            $username,
            $password,
            $driverOptions
        );
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
        return new \Doctrine\DBAL\Platforms\OraclePlatform();
    }

    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new \Doctrine\DBAL\Schema\OracleSchemaManager($conn);
    }
}