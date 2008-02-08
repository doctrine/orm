<?php
class Phonenumber extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('phonenumber', 'string',20);
        $class->setColumn('entity_id', 'integer');
        
        $class->hasOne('Entity', array('local' => 'entity_id', 
                                      'foreign' => 'id', 
                                      'onDelete' => 'CASCADE'));
        
        $class->hasOne('Group', array('local' => 'entity_id', 
                                      'foreign' => 'id', 
                                      'onDelete' => 'CASCADE'));
          
        $class->hasOne('User', array('local' => 'entity_id', 
                                    'foreign' => 'id', 
                                    'onDelete' => 'CASCADE'));
    }
}
