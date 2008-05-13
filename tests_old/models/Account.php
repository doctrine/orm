<?php
class Account extends Doctrine_Entity 
{
    public static function initMetadata($class) 
    {
        $class->setColumn('entity_id', 'integer');
        $class->setColumn('amount', 'integer');
    }
}

