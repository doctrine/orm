<?php
class VersioningTest extends Doctrine_Record 
{
    public static function initMetadata($class)
    {
        $class->setColumn('name', 'string');
        $class->setColumn('version', 'integer');
        $class->actAs('Versionable');
    }
}
