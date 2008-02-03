<?php
abstract class BaseSymfonyRecord extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string', 30);
    }

}
