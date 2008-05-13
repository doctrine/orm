<?php
class gnatEmail extends Doctrine_Entity 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('address', 'string', 150);
    }
    
    
}
