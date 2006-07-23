<?php
class Entity extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn("name","string",30);
        $this->hasColumn("username","string",20);
        $this->hasColumn("password","string",16);
        $this->hasColumn("created","integer",11);
    }
}

class User extends Entity { 
    public function setTableDefinition() {
        // the following method call is needed in
        // one-table-one-class inheritance
        parent::setTableDefinition();
    }
}

class Group extends Entity {
    public function setTableDefinition() {
        // the following method call is needed in
        // one-table-one-class inheritance
        parent::setTableDefinition();
    }
}
?>
