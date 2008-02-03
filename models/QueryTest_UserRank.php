<?php
class QueryTest_UserRank extends Doctrine_Record
{
    public static function initMetadata($class)
    {        
        $class->setColumn('rankId', 'integer', 4, array('primary'));
        $class->setColumn('userId', 'integer', 4, array('primary'));
    }
}
