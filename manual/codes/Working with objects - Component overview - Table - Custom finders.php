<?php
class UserTable extends Doctrine_Table {
    /**
     * you can add your own finder methods here
     */
    public function findByName($name) {
        return $this->getConnection()->query("FROM User WHERE name LIKE '%$name%'");
    }
}
class User extends Doctrine_Record { }

$conn = Doctrine_Manager::getInstance()
           ->openConnection(new PDO("dsn","username","password"));

// doctrine will now check if a class called UserTable exists 
// and if it inherits Doctrine_Table

$table   = $conn->getTable("User");

print get_class($table); // UserTable

$users   = $table->findByName("Jack");

?>
