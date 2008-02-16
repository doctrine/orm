<?php
class MysqlTestRecord extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', null, array('primary' => true));
        $class->setColumn('code', 'integer', null, array('primary' => true));

        $class->setTableOption('type', 'INNODB');
    }
}
