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
}
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        // validators 'email' and 'unique' used
        $this->hasColumn("address","string",150, array("email", "unique" => true));
    }
    protected function validate() {
        if ($this->address !== 'the-only-allowed-mail@address.com') {
            // syntax: add(<fieldName>, <error code>)
            $this->errorStack->add('address', 'myCustomErrorCode');
        }
    }
}  
?>
