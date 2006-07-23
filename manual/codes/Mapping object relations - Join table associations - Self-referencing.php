<?php
class User extends Doctrine_Record {
    public function setUp() {
        $this->hasMany("User as Friend","UserReference.user_id-user_id2");
    }
    public function setTableDefinition() {
        $this->hasColumn("name","string",30);
    }
}
class UserReference extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn("user_id","integer");
        $this->hasColumn("user_id2","integer");
    }
}
?>
