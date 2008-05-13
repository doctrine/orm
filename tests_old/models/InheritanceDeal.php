<?php
class InheritanceDeal extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setTableName('inheritance_deal');
        
        $class->setColumn('id', 'integer', 4, array (  'primary' => true,  'autoincrement' => true,));
        $class->setColumn('name', 'string', 255, array ());
        $class->hasMany('InheritanceUser as Users', array('refClass' => 'InheritanceDealUser', 'local' => 'entity_id', 'foreign' => 'user_id'));
    }
}