<?php
class MysqlIndexTestRecord extends Doctrine_Entity
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', null);
        $class->setColumn('code', 'integer', 4);
        $class->setColumn('content', 'string', 4000);

        $class->addIndex('content',  array('fields' => 'content', 'type' => 'fulltext'));
        $class->addIndex('namecode', array('fields' => array('name', 'code'),
                                       'type'   => 'unique'));

        $class->setTableOption('type', 'MYISAM');

    }
}
