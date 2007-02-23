<?php
class Entity extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("name","string",30);
        $this->hasColumn("username","string",20);
        $this->hasColumn("password","string",16);
        $this->hasColumn("created","integer",11);                                     	
    }
}

class User extends Entity { }

class Group extends Entity { }
?>
