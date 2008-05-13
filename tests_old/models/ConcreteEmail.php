<?php
class ConcreteEmail extends Doctrine_Entity
{
    public static function initMetadata($class)
    {
        $class->loadTemplate('EmailTemplate');
    }
}
