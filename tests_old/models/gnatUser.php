<?php 
class gnatUserTable { }

class gnatUser extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('name', 'string', 150);
        $class->setColumn('foreign_id', 'integer', 10, array ('unique' => true));
        $class->hasOne('gnatEmail as Email', array('local'=> 'foreign_id', 'foreign'=>'id', 'onDelete'=>'CASCADE'));
    }    
}

