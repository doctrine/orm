<?php
class Entity extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('id', 'integer',20, array('autoincrement' => true, 'primary' => true));
        $class->setColumn('name', 'string',50);
        $class->setColumn('loginname', 'string',20, array('unique' => true, 'validators' => array('unique')));
        $class->setColumn('password', 'string',16);
        $class->setColumn('type', 'integer');
        $class->setColumn('created', 'integer',11);
        $class->setColumn('updated', 'integer',11);
        $class->setColumn('email_id', 'integer');
        
        $class->setSubclasses(array('Group', 'User'));
        $class->setInheritanceType(Doctrine::INHERITANCE_TYPE_SINGLE_TABLE, array(
                'discriminatorColumn' => 'type',
                'discriminatorMap' => array(0 => 'User', 1 => 'Group', 2 => 'Entity')
                ));
        
        $class->hasOne('Email', array('local' => 'email_id'));
        $class->hasMany('Phonenumber', array('local' => 'id', 'foreign' => 'entity_id'));
        $class->hasOne('Account', array('foreign' => 'entity_id'));
        $class->hasMany('Entity', array('local' => 'entity1',
            'refClass' => 'EntityReference',
            'foreign' => 'entity2',
            'equal'    => true));
    }
}
