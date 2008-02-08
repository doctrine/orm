<?php
class ConcreteInheritanceTestParent extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string');
    }
}

class ConcreteInheritanceTestChild extends ConcreteInheritanceTestParent
{
    public static function initMetadata($class)
    {
        $class->setColumn('age', 'integer');
    }
}
