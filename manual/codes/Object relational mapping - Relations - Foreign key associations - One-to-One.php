<?php
class User extends Doctrine_Record {
    public function setUp() {
        $this->hasOne('Address','Address.user_id');
        $this->ownsOne('Email','User.email_id');
        $this->ownsMany('Phonenumber','Phonenumber.user_id');
    }
    public function setTableDefition() {
        $this->hasColumn('name','string',50);
        $this->hasColumn('loginname','string',20);
        $this->hasColumn('password','string',16);

        // foreign key column for email ID
        $this->hasColumn('email_id','integer');
    }
}
class Email extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('address','string',150);
    }
}
class Address extends Doctrine_Record { 
    public function setTableDefinition() {
        $this->hasColumn('street','string',50);
        $this->hasColumn('user_id','integer');
    }
} 
?>
