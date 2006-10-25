<?php

class RelationTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('child_id', 'integer');
    }
    public function setUp() {
        $this->ownsMany('OwnsOneToManyWithAlias as AliasO2M', 'AliasO2M.component_id');
    }
}
class RelationTestChild extends RelationTest {
    public function setUp() {
        $this->hasOne('RelationTest as Parent', 'RelationTestChild.child_id');

        $this->ownsMany('RelationTestChild as Children', 'RelationTestChild.child_id');
    }
}

class HasOneToOne extends Doctrine_Record {

}
class HasOneToOneWithAlias extends Doctrine_Record {

}

class HasManyWithAlias extends Doctrine_Record {

}
class OwnsOneToManyWithAlias extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('component_id', 'integer');
    }
    public function setUp() {

    }
}

class Doctrine_Relation_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() {
        parent::prepareTables();
    }


    public function testOneToManyTreeRelationWithConcreteInheritance() {
        $component = new RelationTestChild();
        
        try {
            $rel = $component->getTable()->getRelation('Children');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($rel instanceof Doctrine_Relation_ForeignKey);
        
        $this->assertTrue($component->Children instanceof Doctrine_Collection);
        $this->assertTrue($component->Children[0] instanceof RelationTestChild);
    }


    public function testOneToOneTreeRelationWithConcreteInheritance() {
        $component = new RelationTestChild();
        
        try {
            $rel = $component->getTable()->getRelation('Parent');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($rel instanceof Doctrine_Relation_LocalKey);
    }
    public function testOneToManyOwnsRelationWithAliases() {

        
        $component = new RelationTest();
        
        try {
            $rel = $component->getTable()->getRelation('AliasO2M');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }

        $this->assertTrue($rel instanceof Doctrine_Relation_ForeignKey);
    }

    public function testManyToManyRelation() {
        $this->manager->setAttribute(Doctrine::ATTR_CREATE_TABLES, false);
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
        $this->manager->setAttribute(Doctrine::ATTR_CREATE_TABLES, true);
    }


}
