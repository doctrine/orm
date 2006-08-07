<?php
require_once("UnitTestCase.php");

class Doctrine_ValueHolder_TestCase extends Doctrine_UnitTestCase {
    public function testGetSet() {
        $this->valueHolder->data[0] = 'first';
        
        $this->assertEqual($this->valueHolder->data[0], 'first');
        $this->assertEqual($this->valueHolder[0], 'first');
        $this->assertEqual($this->valueHolder->get(0), 'first');

        $this->valueHolder->data['key'] = 'second';
        
        $this->assertEqual($this->valueHolder->data['key'], 'second');
        $this->assertEqual($this->valueHolder->key, 'second');
        $this->assertEqual($this->valueHolder['key'], 'second');
        $this->assertEqual($this->valueHolder->get('key'), 'second');
    }
    public function testSimpleQuery() {
        $q = new Doctrine_Query($this->session);
        $q->from("User");
        $users = $q->execute(array(), Doctrine::RETURN_VHOLDER);
        $this->assertEqual($users->count(), 8);


    }
    public function testQueryWithOneToManyRelation() {
        $q = new Doctrine_Query($this->session);
        $q->from("User.Phonenumber");
        $users = $q->execute(array(), Doctrine::RETURN_VHOLDER);
        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users[0] instanceof Doctrine_ValueHolder);
        $this->assertTrue($users[3] instanceof Doctrine_ValueHolder);
        $this->assertTrue($users[7] instanceof Doctrine_ValueHolder);

        $this->assertEqual(count($users[0]->Phonenumber), 1);
        $this->assertEqual(count($users[1]->Phonenumber), 3);
        $this->assertEqual(count($users[2]->Phonenumber), 1);
        $this->assertEqual(count($users[3]->Phonenumber), 1);
        $this->assertEqual(count($users[4]->Phonenumber), 3);
    }
    public function testDelete() {
        $f = false;
        try {
            $this->valueHolder->delete();
        } catch(Doctrine_Exception $e) {
            $f = true;
        }
        $this->assertTrue($f);
    }
    public function testSave() {
        $f = false;
        try {
            $this->valueHolder->save();
        } catch(Doctrine_Exception $e) {
            $f = true;
        }
        $this->assertTrue($f);
    }
}
?>
