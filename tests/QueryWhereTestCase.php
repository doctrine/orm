<?php
class Doctrine_Query_Where_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() { 
        $this->tables = array('entity');
        parent::prepareTables();
    }
    public function testQueryWithDirectParameterSetting() {
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
    public function testQueryWithDirectMultipleParameterSetting() {
        $user = new User();
        $user->name = 'someone 2';
        $user->save();

        $q = new Doctrine_Query();
        
        $q->from('User(id)')->addWhere('User.id IN (?, ?)',array(1,2));

        $users = $q->execute();
        
        $this->assertEqual($users->count(), 2);
        $this->assertEqual($users[0]->name, 'someone');
        $this->assertEqual($users[1]->name, 'someone 2');
    }
}
?>
