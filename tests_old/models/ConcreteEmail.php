<?php
class ConcreteEmail extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->loadTemplate('EmailTemplate');
    }
}
