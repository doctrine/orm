<?php
class InheritanceEntityUser extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setInheritanceType(Doctrine::INHERITANCETYPE_SINGLE_TABLE, array(
                'discriminatorColumn' => 'type',
                'discriminatorMap' => array(1 => 'InheritanceDealUser', 2 => 'InheritanceEntityUser')
                ));
        $class->setSubclasses(array('InheritanceDealUser'));
        $class->setTableName('inheritance_entity_user');
        $class->setColumn('type', 'integer', 4, array (  'primary' => true,));
        $class->setColumn('user_id', 'integer', 4, array (  'primary' => true,));
        $class->setColumn('entity_id', 'integer', 4, array (  'primary' => true,));
    }
}

class InheritanceDealUser extends InheritanceEntityUser
{
    public static function initMetadata($class)
    {
        $class->setColumn('user_id', 'integer', 4, array (  'primary' => true,));
        $class->setColumn('entity_id', 'integer', 4, array (  'primary' => true,));
        $class->hasOne('InheritanceUser as User', array('local' => 'user_id', 'foreign' => 'id'));
        $class->hasOne('InheritanceDeal as Deal', array('local' => 'entity_id', 'foreign' => 'id'));
    }
}