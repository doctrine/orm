<?php
class QueryTest_Item extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->setColumn('price', 'decimal');
        $class->setColumn('quantity', 'integer');
    }
}

