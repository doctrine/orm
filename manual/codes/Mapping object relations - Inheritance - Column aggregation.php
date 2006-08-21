<?php
class Entity extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn("name","string",30);
        $this->hasColumn("username","string",20);
        $this->hasColumn("password","string",16);
        $this->hasColumn("created","integer",11);
        
        // this column is used for column 
        // aggregation inheritance
        $this->hasColumn("type", "integer", 11);
    }
}

class User extends Entity {
    public function setUp() {
        $this->setInheritanceMap(array("type"=>1));
    }
}

class Group extends Entity {
    public function setUp() {
        $this->setInheritanceMap(array("type"=>2));
    }
}
?>
