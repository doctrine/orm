<?php 

class Doctrine_TestUtil
{    
    public static function getConnection()
    {
        if (isset($GLOBALS['db_type'], $GLOBALS['db_username'], $GLOBALS['db_password'],
                $GLOBALS['db_host'], $GLOBALS['db_name'])) {
            $params = array(
                'driver' => $GLOBALS['db_type'],
                'user' => $GLOBALS['db_username'],
                'password' => $GLOBALS['db_password'],
                'host' => $GLOBALS['db_host'],
                'database' => $GLOBALS['db_name']
            );
            //$dsn = "{$GLOBALS['db_type']}://{$GLOBALS['db_username']}:{$GLOBALS['db_password']}@{$GLOBALS['db_host']}/{$GLOBALS['db_name']}";
            //return Doctrine_Manager::connection($dsn, 'testconn');
        } else {
            $params = array(
                'driver' => 'pdo_sqlite',
                'memory' => true
            );
        }
        
        return Doctrine_DBAL_DriverManager::getConnection($params);
    }
    /*
    public static function autoloadModel($className)
    {
        $modelDir = dirname(__CLASS__) . '/../models/';
        $fileName = $modelDir . str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
        if (file_exists($fileName)) {
            require $fileName;
        }
    }*/
}