<?php
class ValidatorTest_DateModel extends Doctrine_Entity {
    public static function initMetadata($class) {
        $class->setColumn('birthday', 'date', null,
                array('validators' => array('past')));
        $class->setColumn('death', 'date', null,
                array('validators' => array('future')));
    }
}
