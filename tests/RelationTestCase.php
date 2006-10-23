<?php

class RelationTest extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn("child_id", "integer");
    }
    public function setUp() {
        $this->ownsMany('OwnsOneToManyWithAlias as AliasO2M', 'AliasO2M.component_id');
        $this->hasMany('HasManyToManyWithAlias as AliasM2M', 'JoinTable.c1_id');
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
class JoinTable extends Doctrine_Record {
    public function setTableDefinition() {
        $this->hasColumn('c1_id', 'integer');
        $this->hasColumn('c2_id', 'integer');
    }
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
class HasManyToManyWithAlias extends Doctrine_Record {
    public function setTableDefinition() { 
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp() {
        $this->hasMany('RelationTest as AliasM2M', 'JoinTable.c2_id');
    }
}
class Doctrine_Relation_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array('HasManyToManyWithAlias', 'RelationTest', 'JoinTable');
        
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
    public function testManyToManyHasRelationWithAliases() {
        $component = new RelationTest();
        
        try {
            $rel = $component->getTable()->getRelation('AliasM2M');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $this->assertTrue($rel instanceof Doctrine_Relation_Association);
        
        $this->assertTrue($component->AliasM2M instanceof Doctrine_Collection);

        $component->AliasM2M[0]->name = '1';
        $component->AliasM2M[1]->name = '2';
        $component->name = '2';
        
        $count = $this->dbh->count();

        $component->save();

        $this->assertEqual($this->dbh->count(), ($count + 5));
        
        $this->assertEqual($component->AliasM2M->count(), 2);
        
        $component = $component->getTable()->find($component->id);
        
        $this->assertEqual($component->AliasM2M->count(), 2);
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
