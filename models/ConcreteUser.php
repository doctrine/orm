<?php
class ConcreteUser extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->loadTemplate('UserTemplate');
    }
}

