<?php
class App_User extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('first_name', 'string', 32);
        $this->hasColumn('last_name', 'string', 32);
        $this->hasColumn('email', 'string', 128, 'email');
        $this->hasColumn('username', 'string', 16, 'unique, nospace');
        $this->hasColumn('password', 'string', 128, 'notblank');
        $this->hasColumn('country', 'string', 2, 'country');
        $this->hasColumn('zipcode', 'string', 9, 'nospace');
    }
    public function setUp() {
        $this->hasMany('App', 'App.user_id');
    }    
}
