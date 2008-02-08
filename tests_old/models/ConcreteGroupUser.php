<?php
class ConcreteGroupUser extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->loadTemplate('GroupUserTemplate');
    }
}
