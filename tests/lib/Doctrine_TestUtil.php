<?php 

class Doctrine_TestUtil
{
    
    public static function getConnection()
    {
        if (isset($GLOBALS['db_type'], $GLOBALS['db_username'], $GLOBALS['db_password'],
                $GLOBALS['db_host'], $GLOBALS['db_name'])) {
            $dsn = "{$GLOBALS['db_type']}://{$GLOBALS['db_username']}:{$GLOBALS['db_password']}@{$GLOBALS['db_host']}/{$GLOBALS['db_name']}";
            return Doctrine_Manager::connection($dsn, 'testconn');
        } else {
            return Doctrine_Manager::connection(new PDO('sqlite::memory:'), 'testconn');
        }        
    }
    
}