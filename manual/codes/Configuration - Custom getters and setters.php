<?php
class Customer extends Doctrine_Record {
    public function setUp() {
        // setup code goes here
    }
    public function setTableDefinition() {
        // table definition code goes here                                     	
    }

    public function getAvailibleProducts() {
        // some code
    }
    public function setName($name) {
        if($this->isValidName($name))
            $this->set("name",$name);
    }
    public function getName() {
        return $this->get("name");
    }
}
?>
