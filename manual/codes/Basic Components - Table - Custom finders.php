<?php
class UserTable extends Doctrine_Table {
    /**
     * you can add your own finder methods here
     */
    public function findByName($name) {
        return $this->getSession()->query("FROM User WHERE name LIKE '%$name%'");
    }
}
class User extends Doctrine_Record { }

$session = Doctrine_Manager::getInstance()
           ->openSession(new PDO("dsn","username","password"));

// doctrine will now check if a class called UserTable exists 
// and if it inherits Doctrine_Table

$table   = $session->getTable("User");

print get_class($table); // UserTable

$users   = $table->findByName("Jack");

?>
