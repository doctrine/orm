<?php
class MysqlTestRecord extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', null, 'primary');
        $class->setColumn('code', 'integer', null, 'primary');

        $class->setTableOption('type', 'INNODB');
    }
}
