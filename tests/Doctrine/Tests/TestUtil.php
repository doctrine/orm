<?php

namespace Doctrine\Tests;

/**
 * TestUtil is a class with static utility methods used during tests.
 * 
 * @author robo
 */
class TestUtil
{
    /**
     * Gets a <b>real</b> database connection using the following parameters
     * of the $GLOBALS array:
     * 
     * 'db_type' : The name of the Doctrine DBAL database driver to use.
     * 'db_username' : The username to use for connecting.
     * 'db_password' : The password to use for connecting.
     * 'db_host' : The hostname of the database to connect to.
     * 'db_name' : The name of the database to connect to.
     * 'db_port' : The port of the database to connect to.
     * 
     * Usually these variables of the $GLOBALS array are filled by PHPUnit based
     * on an XML configuration file. If no such parameters exist, an SQLite
     * in-memory database is used.
     * 
     * IMPORTANT:
     * 1) Each invocation of this method returns a NEW database connection.
     * 2) The database is dropped and recreated to ensure it's clean.
     * 
     * @return Doctrine\DBAL\Connection The database connection instance.
     */
    public static function getConnection()
    {
        if (isset($GLOBALS['db_type'], $GLOBALS['db_username'], $GLOBALS['db_password'],
                $GLOBALS['db_host'], $GLOBALS['db_name'], $GLOBALS['db_port'])) {
            $params = array(
                'driver' => $GLOBALS['db_type'],
                'user' => $GLOBALS['db_username'],
                'password' => $GLOBALS['db_password'],
                'host' => $GLOBALS['db_host'],
                'dbname' => $GLOBALS['db_name'],
                'port' => $GLOBALS['db_port']
            );
            $conn = \Doctrine\DBAL\DriverManager::getConnection($params);
            $conn->getSchemaManager()->dropAndCreateDatabase();
            $conn->close();
            $conn->connect();
        } else {
            $params = array(
                'driver' => 'pdo_sqlite',
                'memory' => true
            );
            $conn = \Doctrine\DBAL\DriverManager::getConnection($params);
        }

        return $conn;
    }
}