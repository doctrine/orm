<?php
class gnatEmail extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('address', 'string', 150);
    }
    
    
}
