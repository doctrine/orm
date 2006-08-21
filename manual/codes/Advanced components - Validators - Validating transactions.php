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
        // specialized validators 'email' and 'unique' used
        $this->hasColumn("address","string",150,"email|unique");
    }
}
$conn = Doctrine_Manager::getInstance()->openConnection(new PDO("dsn","username","password"));
$user = new User();
$user->name = "this is an example of too long name";

$user->save(); // throws a Doctrine_Validator_Exception

$user->name  = "valid name";
$user->created = "not valid"; // not valid type
$user->save(); // throws a Doctrine_Validator_Exception


$user->created = time();
$user->Email->address = "drink@.."; // not valid email address
$user->save(); // throws a Doctrine_Validator_Exception

$user->Email->address = "drink@drinkmore.info";
$user->save(); // saved


$user   = $conn->create("User");
$user->Email->address = "drink@drinkmore.info"; // not unique!
$user->save(); // throws a Doctrine_Validator_Exception     
?>
