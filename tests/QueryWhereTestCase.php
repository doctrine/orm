<?php
class Doctrine_Query_Where_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() { 
        $this->tables = array('entity');
        parent::prepareTables();
    }
    public function testDirectParameterSetting() {
        $this->connection->clear();

        $user = new User();
        $user->name = 'someone';
        $user->save();

        $q = new Doctrine_Query();

        $q->from('User(id)')->addWhere('User.id = ?',1);

        $users = $q->execute();

        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->name, 'someone');
    }
    public function testDirectMultipleParameterSetting() {
        $user = new User();
        $user->name = 'someone.2';
        $user->save();

        $q = new Doctrine_Query();

        $q->from('User(id)')->addWhere('User.id IN (?, ?)',array(1,2));

        $users = $q->execute();

        $this->assertEqual($users->count(), 2);
        $this->assertEqual($users[0]->name, 'someone');
        $this->assertEqual($users[1]->name, 'someone.2');
    }
    public function testOperatorWithNoTrailingSpaces() {
        $q = new Doctrine_Query();
        
        $q->from('User(id)')->where("User.name='someone'");

        $users = $q->execute();
        $this->assertEqual($users->count(), 1);
        
        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id FROM entity WHERE entity.name = 'someone' AND (entity.type = 0)");
    }
    public function testOperatorWithNoTrailingSpaces2() {
        $q = new Doctrine_Query();
        
        $q->from('User(id)')->where("User.name='foo.bar'");

        $users = $q->execute();
        $this->assertEqual($users->count(), 0);
        
        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id FROM entity WHERE entity.name = 'foo.bar' AND (entity.type = 0)");
    }
    public function testOperatorWithSingleTrailingSpace() {
        $q = new Doctrine_Query();
        
        $q->from('User(id)')->where("User.name= 'foo.bar'");

        $users = $q->execute();
        $this->assertEqual($users->count(), 0);
        
        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id FROM entity WHERE entity.name = 'foo.bar' AND (entity.type = 0)");
    }
    public function testOperatorWithSingleTrailingSpace2() {
        $q = new Doctrine_Query();
        
        $q->from('User(id)')->where("User.name ='foo.bar'");

        $users = $q->execute();
        $this->assertEqual($users->count(), 0);
        
        $this->assertEqual($q->getQuery(), "SELECT entity.id AS entity__id FROM entity WHERE entity.name = 'foo.bar' AND (entity.type = 0)");
    }
}
?>
