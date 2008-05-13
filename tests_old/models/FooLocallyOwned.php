<?php
class FooLocallyOwned extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', 200);
    }
}

