<?php
class ConcreteGroup extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->loadTemplate('GroupTemplate');
    }
}
