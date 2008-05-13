<?php
class InheritanceUser extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setTableName('inheritance_user');

        $class->setColumn('id', 'integer', 4, array (  'primary' => true,  'autoincrement' => true,));
        $class->setColumn('username', 'string', 128, array (  'notnull' => true,));
        $class->hasMany('InheritanceDeal as Deals', array('refClass' => 'InheritanceDealUser', 'local' => 'user_id', 'foreign' => 'entity_id'));
    }
}