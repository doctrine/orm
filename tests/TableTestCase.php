<?php
require_once("UnitTestCase.php");
class Doctrine_TableTestCase extends Doctrine_UnitTestCase {
    public function testBind() {
        $table = $this->session->getTable("User");
    }

    public function testGetIdentifier() {
        $table = $this->session->getTable("User");
    }
    public function testGetForeignKey() {
        $fk = $this->objTable->getForeignKey("Group");
        $this->assertTrue($fk instanceof Doctrine_Association);
        $this->assertTrue($fk->getTable() instanceof Doctrine_Table);
        $this->assertTrue($fk->getType() == Doctrine_Relation::MANY_AGGREGATE);
        $this->assertTrue($fk->getLocal() == "user_id");
        $this->assertTrue($fk->getForeign() == "group_id");

        $fk = $this->objTable->getForeignKey("Email");
        $this->assertTrue($fk instanceof Doctrine_LocalKey);
        $this->assertTrue($fk->getTable() instanceof Doctrine_Table);
        $this->assertTrue($fk->getType() == Doctrine_Relation::ONE_COMPOSITE);
        $this->assertTrue($fk->getLocal() == "email_id");
        $this->assertTrue($fk->getForeign() == $fk->getTable()->getIdentifier());


        $fk = $this->objTable->getForeignKey("Phonenumber");
        $this->assertTrue($fk instanceof Doctrine_ForeignKey);
        $this->assertTrue($fk->getTable() instanceof Doctrine_Table);
        $this->assertTrue($fk->getType() == Doctrine_Relation::MANY_COMPOSITE);
        $this->assertTrue($fk->getLocal() == $this->objTable->getIdentifier());
        $this->assertTrue($fk->getForeign() == "entity_id");
        

    }
    public function testGetComponentName() {
        $this->assertTrue($this->objTable->getComponentName() == "User");
    } 
    public function testGetTableName() {
        $this->assertTrue($this->objTable->getTableName() == "entity");
    } 
    public function testGetSession() {
        $this->assertTrue($this->objTable->getSession() instanceof Doctrine_Session);
    }
    public function testGetData() {
        $this->assertTrue($this->objTable->getData() == array());
    }
    public function testSetSequenceName() {
        $this->objTable->setSequenceName("test-seq");
        $this->assertEqual($this->objTable->getSequenceName(),"test-seq");
        $this->objTable->setSequenceName(null);
    }
    public function testCreate() {
        $record = $this->objTable->create();
        $this->assertTrue($record instanceof Doctrine_Record);
        $this->assertTrue($record->getState() == Doctrine_Record::STATE_TCLEAN);
    }
    public function testFind() {
        $record = $this->objTable->find(4);
        $this->assertTrue($record instanceof Doctrine_Record);
        
        try {
            $record = $this->objTable->find(123);
        } catch(Exception $e) {
            $this->assertTrue($e instanceOf Doctrine_Find_Exception);
        }
    }
    public function testFindAll() {
        $users = $this->objTable->findAll();
        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users instanceof Doctrine_Collection);
    }
    public function testFindBySql() {
        $users = $this->objTable->findBySql("name LIKE '%Arnold%'");
        $this->assertEqual($users->count(), 1);
        $this->assertTrue($users instanceof Doctrine_Collection);
    }
    public function testGetProxy() {
        $user = $this->objTable->getProxy(4);
        $this->assertTrue($user instanceof Doctrine_Record);

        try {
            $record = $this->objTable->find(123);
        } catch(Exception $e) {
            $this->assertTrue($e instanceOf Doctrine_Find_Exception);
        }
    }
    public function testGetColumns() {
        $columns = $this->objTable->getColumns();
        $this->assertTrue(is_array($columns));

    }
    public function testIsNewEntry() {
        $this->assertFalse($this->objTable->isNewEntry());
    }
    public function testApplyInheritance() {
        $this->assertEqual($this->objTable->applyInheritance("id = 3"), "id = 3 AND type = ?");
    }
}
?>
