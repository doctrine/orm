<?php
class JC1 extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('c1_id', 'integer');
        $class->setColumn('c2_id', 'integer');
    }
}

