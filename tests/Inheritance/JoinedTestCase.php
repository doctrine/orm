<?php

class Doctrine_Inheritance_Joined_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }

    public function prepareTables()
    {
        $this->tables[] = 'CTI_User';
        $this->tables[] = 'CTI_Manager';
        $this->tables[] = 'CTI_Customer';
        $this->tables[] = 'CTI_SuperManager';
        
        parent::prepareTables();   
    }
    
    public function setUp()
    {
        parent::setUp();
        $this->prepareTables();
    }

    public function testMetadataSetup()
    {        
        $suManagerTable = $this->conn->getTable('CTI_SuperManager');
        $userTable = $this->conn->getTable('CTI_User');
        $customerTable = $this->conn->getTable('CTI_Customer');
        $managerTable = $this->conn->getTable('CTI_Manager');
        $this->assertTrue($suManagerTable !== $userTable);
        $this->assertTrue($suManagerTable !== $customerTable);
        $this->assertTrue($userTable !== $customerTable);
        $this->assertTrue($managerTable !== $suManagerTable);
        
        // expected column counts
        $this->assertEqual(2, count($suManagerTable->getColumns()));
        $this->assertEqual(4, count($userTable->getColumns()));
        $this->assertEqual(2, count($managerTable->getColumns()));
        $this->assertEqual(2, count($customerTable->getColumns()));
        
        // expected table names
        $this->assertEqual('cti_user', $userTable->getTableName());
        $this->assertEqual('cti_manager', $managerTable->getTableName());
        $this->assertEqual('cti_customer', $customerTable->getTableName());
        $this->assertEqual('cti_supermanager', $suManagerTable->getTableName());
        
        // expected joined parents option
        $this->assertEqual(array(), $userTable->getOption('joinedParents'));
        $this->assertEqual(array('CTI_User'), $managerTable->getOption('joinedParents'));
        $this->assertEqual(array('CTI_User'), $customerTable->getOption('joinedParents'));
        $this->assertEqual(array('CTI_Manager', 'CTI_User'), $suManagerTable->getOption('joinedParents'));
        
        // check inheritance map
        $this->assertEqual(array(
                'CTI_User' => array('type' => 1),
                'CTI_Manager' => array('type' => 2),
                'CTI_Customer' => array('type' => 3),
                'CTI_SuperManager' => array('type' => 4)), $userTable->getOption('inheritanceMap'));
                
        
        //$this->assertEqual(array('CTI_User', 'CTI_Manager', ''))
    }
    
    protected function _createManager()
    {
        $manager = new CTI_Manager();
        $manager->salary = 80000;
        $manager->name = 'John Smith';
        try {
            $manager->save();
            $this->pass();
            return $manager;
        } catch (Exception $e) {
            $this->fail("Inserting record in class table inheritance failed: " . $e->getMessage());
        }
    }
    
    protected function _createSuperManager()
    {
        $manager = new CTI_SuperManager();
        $manager->salary = 1000000;
        $manager->name = 'Bill Gates';
        $manager->gosutitle = 'BillyBoy';
        try {
            $manager->save();
            $this->pass();
            return $manager;
        } catch (Exception $e) {
            $this->fail("Inserting record in class table inheritance failed: " . $e->getMessage());
        }
        
    }
    
    public function testSaveInsertsDataAcrossJoinedTablesTransparently()
    {
        $manager = $this->_createManager();
        $this->assertEqual(1, $manager->id);
        $this->assertEqual(80000, $manager->salary);
        $this->assertEqual('John Smith', $manager->name);
        $this->assertEqual(2, $manager->type);
        
        $superManager = $this->_createSuperManager();
        $this->assertEqual(2, $superManager->id);
        $this->assertEqual(1000000, $superManager->salary);
        $this->assertEqual('Bill Gates', $superManager->name);
        $this->assertEqual('BillyBoy', $superManager->gosutitle);
        $this->assertEqual(4, $superManager->type);
    }
    
    public function testUpdateUpdatesDataAcrossJoinedTablesTransparently()
    {
        $manager = $this->_createManager();
        $manager->salary = 90000; // he got a pay rise...
        $manager->name = 'John Locke'; // he got married ...
        try {
            $manager->save();
            $this->pass();
        } catch (Exception $e) {
            $this->fail("Updating record in class table inheritance failed: " . $e->getMessage());
        }  
        $this->assertEqual(1, $manager->id);
        $this->assertEqual(90000, $manager->salary);
        $this->assertEqual('John Locke', $manager->name);
        $this->assertEqual(2, $manager->type);
        
        
        $superManager = $this->_createSuperManager();
        $superManager->salary = 0; // he got fired...
        $superManager->name = 'Bill Clinton'; // he got married ... again
        $superManager->gosutitle = 'Billy the Kid'; // ... and went mad
        try {
            $superManager->save();
            $this->pass();
        } catch (Exception $e) {
            $this->fail("Updating record in class table inheritance failed: " . $e->getMessage());
        }  
        $this->assertEqual(2, $superManager->id);
        $this->assertEqual(0, $superManager->salary);
        $this->assertEqual('Bill Clinton', $superManager->name);
        $this->assertEqual('Billy the Kid', $superManager->gosutitle);
        $this->assertEqual(4, $superManager->type);
    }
}


class CTI_User extends Doctrine_Record
{    
    public function setTableDefinition()
    {
        $this->setInheritanceType(Doctrine::INHERITANCETYPE_JOINED,
                array('CTI_User' => array('type' => 1),
                      'CTI_Manager' => array('type' => 2),
                      'CTI_Customer' => array('type' => 3),
                      'CTI_SuperManager' => array('type' => 4))
        );
        $this->setTableName('cti_user');
        $this->hasColumn('cti_id as id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
        $this->hasColumn('cti_foo as foo', 'integer', 4);
        $this->hasColumn('cti_name as name', 'string', 50);
        $this->hasColumn('type', 'integer', 4);
    }
}

class CTI_Manager extends CTI_User 
{
    public function setTableDefinition()
    {
        $this->setTableName('cti_manager');
        $this->hasColumn('ctim_salary as salary', 'varchar', 50, array());
    }
}

class CTI_Customer extends CTI_User
{
    public function setTableDefinition()
    {
        $this->setTableName('cti_customer');
        $this->hasColumn('ctic_bonuspoints as bonuspoints', 'varchar', 50, array());
    }
}

class CTI_SuperManager extends CTI_Manager
{
    public function setTableDefinition()
    {
        $this->setTableName('cti_supermanager');
        $this->hasColumn('ctism_gosutitle as gosutitle', 'varchar', 50, array());
    }
}
