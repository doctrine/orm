<?php
class NestedSetTest_SingleRootNode extends Doctrine_Record {
    
    public function setTableDefinition() {
        $this->actAs('NestedSet');
        $this->hasColumn('name', 'string', 50, array('notnull'));
    }
    
}
