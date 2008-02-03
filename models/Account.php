<?php
class Account extends Doctrine_Record 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('entity_id', 'integer');
        $class->setColumn('amount', 'integer');
    }
}

