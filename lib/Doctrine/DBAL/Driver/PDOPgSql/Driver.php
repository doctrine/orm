<?php

namespace Doctrine\DBAL\Driver\PDOPgSql;

use Doctrine\DBAL\Platforms;

/**
 * Driver that connects through pdo_pgsql.
 *
 * @since 2.0
 */
class Driver implements \Doctrine\DBAL\Driver
{
    /**
     * Attempts to connect to the database and returns a driver connection on success.
     *
     * @return Doctrine\DBAL\Driver\Connection
     */
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
     * Constructs the Postgres PDO DSN.
     *
     * @return string The DSN.
     */
    private function _constructPdoDsn(array $params)
    {
        $dsn = 'pgsql:';
        if (isset($params['host'])) {
            $dsn .= 'host=' . $params['host'] . ' ';
        }
        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ' ';
        }
        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ' ';
        }

        return $dsn;
    }

    public function getDatabasePlatform()
    {
        return new \Doctrine\DBAL\Platforms\PostgreSqlPlatform();
    }

    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new \Doctrine\DBAL\Schema\PostgreSqlSchemaManager($conn);
    }

    public function getName()
    {
        return 'pdo_pgsql';
    }

    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        $params = $conn->getParams();
        return $params['dbname'];
    }
}