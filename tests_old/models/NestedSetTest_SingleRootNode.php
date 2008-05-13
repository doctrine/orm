<?php
class NestedSetTest_SingleRootNode extends Doctrine_Entity {
    
    public static function initMetadata($class) {
        $class->actAs('NestedSet');
        $class->setColumn('name', 'string', 50, array('notnull'));
    }
    
}
