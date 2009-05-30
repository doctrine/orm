<?php 

namespace Doctrine\Tests;

class TestUtil
{
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
        } else {
            $params = array(
                'driver' => 'pdo_sqlite',
                'memory' => true
            );
        }

        $conn = \Doctrine\DBAL\DriverManager::getConnection($params);
        $conn->getSchemaManager()->dropAndCreateDatabase();

        $conn->close();
        $conn->connect();

        return $conn;
    }
}