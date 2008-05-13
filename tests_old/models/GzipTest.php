<?php
class GzipTest extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('gzip', 'gzip', 100000);
    }
}
