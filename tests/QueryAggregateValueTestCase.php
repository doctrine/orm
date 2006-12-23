<?php
class Doctrine_Query_AggregateValue_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }

    
    public function testInitData() {
        $users = new Doctrine_Collection('User');
        
        $users[0]->name = 'John';
        $users[0]->Phonenumber[0]->phonenumber = '123 123';
        $users[0]->Phonenumber[1]->phonenumber = '222 222';
        $users[0]->Phonenumber[2]->phonenumber = '333 333';

        $users[1]->name = 'John';
        $users[2]->name = 'James';
        $users[2]->Phonenumber[0]->phonenumber = '222 344';
        $users[2]->Phonenumber[1]->phonenumber = '222 344';
        $users[3]->name = 'James';
        $users[3]->Phonenumber[0]->phonenumber = '123 123';

        $users->save();
    }
    public function testRecordSupportsValueMapping() {
        $record = new User();
        
        try { 
            $record->get('count');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }

        $record->mapValue('count', 3);

        try {
            $i = $record->get('count');
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $this->assertEqual($i, 3);
    }
    public function testAggregateValueIsMappedToNewRecordOnEmptyResultSet() {
        $q = new Doctrine_Query();

        $q->select('COUNT(u.id) count')->from('User u');
    
        $users = $q->execute();
        
        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->state(), Doctrine_Record::STATE_TCLEAN);
    }
    public function testAggregateValueIsMappedToRecord() {
        $q = new Doctrine_Query();

        $q->select('u.name, COUNT(u.id) count')->from('User u')->groupby('u.name');
    
        $users = $q->execute();

        $this->assertEqual($users->count(), 2);
        
        $this->assertEqual($users[0]->state(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($users[1]->state(), Doctrine_Record::STATE_PROXY);
        
        $this->assertEqual($users[0]->count, 2);
        $this->assertEqual($users[1]->count, 2);
    }
    public function testAggregateValueMappingSupportsLeftJoins() {
        $q = new Doctrine_Query();

        $q->select('u.name, COUNT(p.id) count')->from('User u')->leftJoin('u.Phonenumber p')->groupby('u.id');
    
        $users = $q->execute();

        $this->assertEqual($users->count(), 4);

        $this->assertEqual($users[0]->Phonenumber[0]->count, 3);
        $this->assertEqual($users[1]->Phonenumber[0]->count, 0);
        $this->assertEqual($users[2]->Phonenumber[0]->count, 2);
        $this->assertEqual($users[3]->Phonenumber[0]->count, 1);
    }
    public function testAggregateValueMappingSupportsInnerJoins() {
        $q = new Doctrine_Query();

        $q->select('u.name, COUNT(p.id) count')->from('User u')->innerJoin('u.Phonenumber p')->groupby('u.id');

        $users = $q->execute();

        $this->assertEqual($users->count(), 3);

        $this->assertEqual($users[0]->Phonenumber[0]->count, 3);
        $this->assertEqual($users[1]->Phonenumber[0]->count, 2);
        $this->assertEqual($users[2]->Phonenumber[0]->count, 1);
    }
}
?>
