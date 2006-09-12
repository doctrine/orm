<?php
class User { 
    public function setTableDefinition() {
        $this->hasColumn("name", "string", 200);
        $this->hasColumn("password", "string", 32);
    }
    public function setPassword($password) {
        return md5($password);
    }
    public function getName($name) {
        return strtoupper($name);
    }
}

$user = new User();

$user->name = 'someone';

print $user->name; // someone

$user->password = '123'; 

print $user->password; // 123

$user->setAttribute(Doctrine::ATTR_LISTENER, new Doctrine_EventListener_AccessorInvoker());

print $user->name; // SOMEONE

$user->password = '123';

print $user->password; // 202cb962ac59075b964b07152d234b70
?>
