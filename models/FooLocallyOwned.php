<?php
class FooLocallyOwned extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', 200);
    }
}

