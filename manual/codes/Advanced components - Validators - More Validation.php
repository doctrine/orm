<?php
class User extends Doctrine_Record {
    public function setUp() {
        $this->ownsOne("Email","User.email_id");
    }
    public function setTableDefinition() {
        // no special validators used only types 
        // and lengths will be validated
        $this->hasColumn("name","string",15);
        $this->hasColumn("email_id","integer");
        $this->hasColumn("created","integer",11);
    }
    // Our own validation
    protected function validate() {
        if ($this->name == 'God') {
            // Blasphemy! Stop that! ;-)
            // syntax: add(<fieldName>, <error code/identifier>)
            $this->getErrorStack()->add('name', 'forbiddenName');
        }
    }
}
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        // validators 'email' and 'unique' used
        $this->hasColumn("address","string",150, array("email", "unique"));
    }
}  
?>
