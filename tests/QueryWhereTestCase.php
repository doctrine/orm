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

        $q->from('User(id)')->addWhere('User.id IN (?, ?)', array(1,2));

        $users = $q->execute();

        $this->assertEqual($users->count(), 2);
        $this->assertEqual($users[0]->name, 'someone');
        $this->assertEqual($users[1]->name, 'someone.2');
    }

    public function testNotInExpression() {
        $q = new Doctrine_Query();

        $q->from('User u')->addWhere('u.id NOT IN (?)', array(1));
        $users = $q->execute();

        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->name, 'someone.2');
    }
    public function testExistsExpression() {
        $q = new Doctrine_Query();
        
        $user = new User();
        $user->name = 'someone with a group';
        $user->Group[0]->name = 'some group';
        $user->save();
        
        // find all users which have groups
        try {
            $q->from('User u')->where('EXISTS (FROM Groupuser(id) WHERE Groupuser.user_id = u.id)');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $users = $q->execute();
        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->name, 'someone with a group');
    }

    public function testNotExistsExpression() {
        $q = new Doctrine_Query();

        // find all users which don't have groups
        try {
            $q->from('User u')->where('NOT EXISTS (FROM Groupuser(id) WHERE Groupuser.user_id = u.id)');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $users = $q->execute();
        $this->assertEqual($users->count(), 2);
        $this->assertEqual($users[0]->name, 'someone');
        $this->assertEqual($users[1]->name, 'someone.2');  
    }
    public function testComponentAliases() {
        $q = new Doctrine_Query();

        $q->from('User(id) u')->addWhere('u.id IN (?, ?)', array(1,2));
        $users = $q->execute();

        $this->assertEqual($users->count(), 2);
        $this->assertEqual($users[0]->name, 'someone');
        $this->assertEqual($users[1]->name, 'someone.2');             

    }
    public function testComponentAliases2() {
        $q = new Doctrine_Query();

        $q->from('User u')->addWhere('u.name = ?', array('someone'));

        $users = $q->execute();

        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->name, 'someone');
    }
    public function testComponentAliases3() {

        $users = $this->connection->query("FROM User u WHERE u.name = ?", array('someone'));

        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->name, 'someone');
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
