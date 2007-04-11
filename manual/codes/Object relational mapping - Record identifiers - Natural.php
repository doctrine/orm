<?php
class User extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name','string',200,'primary');
    }
}
?>
