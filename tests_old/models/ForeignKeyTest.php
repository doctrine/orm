<?php
class ForeignKeyTest extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', null);
        $class->setColumn('code', 'integer', 4);
        $class->setColumn('content', 'string', 4000);
        $class->setColumn('parent_id', 'integer');

        $class->hasOne('ForeignKeyTest as Parent',
                       array('local'    => 'parent_id',
                             'foreign'  => 'id',
                             'onDelete' => 'CASCADE',
                             'onUpdate' => 'RESTRICT')
                       );

        $class->hasMany('ForeignKeyTest as Children', array('local' => 'id', 'foreign' => 'parent_id'));

        $class->setTableOption('type', 'INNODB');
    }
}
