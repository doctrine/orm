<?php
class Doctrine_AccessTestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array("Entity", "User"); 
        parent::prepareTables();
    }
    public function testUnset() {

        

    }
    public function testIsset() {
        $user = new User();
        
        $this->assertTrue(isset($user->name));  
        $this->assertFalse(isset($user->unknown));
        
        $this->assertTrue(isset($user['name']));
        $this->assertFalse(isset($user['unknown']));
        
        $coll = new Doctrine_Collection('User');
        
        $this->assertFalse(isset($coll[0]));
        // test repeated call
        $this->assertFalse(isset($coll[0]));
        $coll[0];
        
        $this->assertTrue(isset($coll[0]));
        // test repeated call
        $this->assertTrue(isset($coll[0]));
    }
    public function testOffsetMethods() {
        $user = new User();
        $this->assertEqual($user["name"],null);

        $user["name"] = "Jack";
        $this->assertEqual($user["name"],"Jack");

        $user->save();

        $user = $this->connection->getTable("User")->find($user->getID());
        $this->assertEqual($user->name,"Jack");

        $user["name"] = "Jack";
        $this->assertEqual($user["name"],"Jack");
        $user["name"] = "zYne";
        $this->assertEqual($user["name"],"zYne");
    }
    public function testOverload() {
        $user = new User();
        $this->assertEqual($user->name,null);

        $user->name = "Jack";

        $this->assertEqual($user->name,"Jack");
        
        $user->save();

        $user = $this->connection->getTable("User")->find($user->getID());
        $this->assertEqual($user->name,"Jack");

        $user->name = "Jack";
        $this->assertEqual($user->name,"Jack");
        $user->name = "zYne";
        $this->assertEqual($user->name,"zYne");
    }
    public function testSet() {
        $user = new User();
        $this->assertEqual($user->get("name"),null);

        $user->set("name","Jack");
        $this->assertEqual($user->get("name"),"Jack");

        $user->save();

        $user = $this->connection->getTable("User")->find($user->getID());

        $this->assertEqual($user->get("name"),"Jack");

        $user->set("name","Jack");
        $this->assertEqual($user->get("name"),"Jack");
    }
}
?>
