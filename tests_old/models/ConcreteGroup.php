<?php
class ConcreteGroup extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->loadTemplate('GroupTemplate');
    }
}
