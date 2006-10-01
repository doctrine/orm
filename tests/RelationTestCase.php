<?php
class Doctrine_Relation_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() { 
    $this->tables = array();
    }
    public function testManyToManyRelation() {
        $user = new User();
        
        // test that join table relations can be initialized even before the association have been initialized
        try {
            $user->Groupuser;
            $this->pass();
        } catch(Doctrine_Table_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($user->getTable()->getRelation('Groupuser') instanceof Doctrine_Relation_ForeignKey);
        $this->assertTrue($user->getTable()->getRelation('Group') instanceof Doctrine_Relation_Association);
    }
    public function testOneToOneLocalKeyRelation() {
        $user = new User();
        
        $this->assertTrue($user->getTable()->getRelation('Email') instanceof Doctrine_Relation_LocalKey);
    }
    public function testOneToOneForeignKeyRelation() {
        $user = new User();
        
        $this->assertTrue($user->getTable()->getRelation('Account') instanceof Doctrine_Relation_ForeignKey);
    }
    public function testOneToManyForeignKeyRelation() {
        $user = new User();
        
        $this->assertTrue($user->getTable()->getRelation('Phonenumber') instanceof Doctrine_Relation_ForeignKey);
    }
}
