<?php
class RelationTest extends Doctrine_Record {
    public function setTableDefinition() {

    }
    public function setUp() {
        $this->ownsMany('OwnsOneToManyWithAlias as AliasO2M', 'AliasO2M.component_id');
        $this->hasMany('HasManyToManyWithAlias as AliasM2M', 'JoinTable.c1_id');
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
    public function setTableDefinition() { }
    public function setUp() {
        $this->hasMany('RelationTest as AliasM2M', 'JoinTable.c2_id');
    }
}
class Doctrine_Relation_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() { 
        $this->tables = array();
    }

    public function testOneToManyOwnsRelationWithAliases() {
        $this->manager->setAttribute(Doctrine::ATTR_CREATE_TABLES, false);  
        
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
        $this->manager->setAttribute(Doctrine::ATTR_CREATE_TABLES, true);
    }

}
