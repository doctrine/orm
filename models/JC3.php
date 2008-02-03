<?php
class JC3 extends Doctrine_Record {
    public static function initMetadata($class) {
        $class->setColumn('c1_id', 'integer');
        $class->setColumn('c2_id', 'integer');
    }
}

