<?php
class ConcreteUser extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->loadTemplate('UserTemplate');
    }
}

