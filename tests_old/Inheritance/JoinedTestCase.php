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
        $suManagerTable = $this->conn->getMetadata('CTI_SuperManager');
        $userTable = $this->conn->getMetadata('CTI_User');
        $customerTable = $this->conn->getMetadata('CTI_Customer');
        $managerTable = $this->conn->getMetadata('CTI_Manager');
        $this->assertTrue($suManagerTable !== $userTable);
        $this->assertTrue($suManagerTable !== $customerTable);
        $this->assertTrue($userTable !== $customerTable);
        $this->assertTrue($managerTable !== $suManagerTable);
        
        // expected column counts
        $this->assertEqual(6, count($suManagerTable->getColumns()));
        $this->assertEqual(4, count($userTable->getColumns()));
        $this->assertEqual(5, count($managerTable->getColumns()));
        $this->assertEqual(5, count($customerTable->getColumns()));
        
        // expected table names
        $this->assertEqual('cti_user', $userTable->getTableName());
        $this->assertEqual('cti_manager', $managerTable->getTableName());
        $this->assertEqual('cti_customer', $customerTable->getTableName());
        $this->assertEqual('cti_supermanager', $suManagerTable->getTableName());
        
        // expected joined parents option
        $this->assertEqual(array(), $userTable->getParentClasses());
        $this->assertEqual(array('CTI_User'), $managerTable->getParentClasses());
        $this->assertEqual(array('CTI_User'), $customerTable->getParentClasses());
        $this->assertEqual(array('CTI_Manager', 'CTI_User'), $suManagerTable->getParentClasses());
        
        // check inheritance map
        $this->assertEqual(array(1 => 'CTI_User', 2 => 'CTI_Manager',
                3 => 'CTI_Customer', 4 => 'CTI_SuperManager'), $userTable->getInheritanceOption('discriminatorMap'));
        
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
        $this->assertTrue($manager instanceof CTI_Manager);
        
        $superManager = $this->_createSuperManager();
        $this->assertEqual(2, $superManager->id);
        $this->assertEqual(1000000, $superManager->salary);
        $this->assertEqual('Bill Gates', $superManager->name);
        $this->assertEqual('BillyBoy', $superManager->gosutitle);
        $this->assertTrue($superManager instanceof CTI_SuperManager);
    }
    
    public function testUpdateUpdatesOnlyChangedFields()
    {
        $manager = $this->_createManager();        
        try {
            $manager->salary = 12;
            $manager->save();
            $this->pass();
        } catch (Exception $e) {
            $this->fail("Update failed [{$e->getMessage()}].");
        }
        
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
        $this->assertTrue($manager instanceof CTI_Manager);
        
        
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
        $this->assertTrue($superManager instanceof CTI_SuperManager);
    }
    
    public function testDqlQueryJoinsTransparentlyAcrossParents()
    {
        $this->_createManager();
        $this->conn->clear('CTI_Manager');
        
        $query = $this->conn->createQuery();
        $query->parseQuery("SELECT m.* FROM CTI_Manager m");
        $manager = $query->execute()->getFirst();
        
        $this->assertTrue($manager instanceof CTI_Manager);
        $this->assertEqual(1, $manager->id);
        $this->assertEqual(80000, $manager->salary);
        $this->assertEqual('John Smith', $manager->name);
    }
    
    public function testQueryingBaseClassOuterJoinsSubClassesAndReturnsSubclassInstances()
    {
        $this->_createManager();
        $this->conn->clear('CTI_Manager');
        $this->conn->clear('CTI_User');
        
        $query = $this->conn->createQuery();
        $query->parseQuery("SELECT u.* FROM CTI_User u");
        //echo $query->getSql();
        $user = $query->execute()->getFirst();
        
        $this->assertTrue($user instanceof CTI_Manager);
        $this->assertEqual(1, $user->id);
        $this->assertEqual(80000, $user->salary);
        $this->assertEqual('John Smith', $user->name);
    }
}


class CTI_User extends Doctrine_Record
{    
    public static function initMetadata($class)
    {
        $class->setInheritanceType(Doctrine::INHERITANCE_TYPE_JOINED, array(
                'discriminatorColumn' => 'dtype',
                'discriminatorMap' => array(1 => 'CTI_User', 2 => 'CTI_Manager',
                        3 => 'CTI_Customer', 4 => 'CTI_SuperManager')
                ));
        $class->setSubclasses(array('CTI_Manager', 'CTI_Customer', 'CTI_SuperManager'));  
        $class->setTableName('cti_user');
        $class->setColumn('cti_id as id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
        $class->setColumn('cti_foo as foo', 'integer', 4);
        $class->setColumn('cti_name as name', 'string', 50, array('notnull' => true));
        $class->setColumn('dtype', 'integer', 2);
    }    
}

class CTI_Manager extends CTI_User 
{
    protected $name;
    
    public static function initMetadata($class)
    {
        $class->setTableName('cti_manager');
        $class->setSubclasses(array('CTI_SuperManager')); 
        $class->setColumn('ctim_salary as salary', 'varchar', 50, array());
    }
}

class CTI_Customer extends CTI_User
{
    public static function initMetadata($class)
    {
        $class->setTableName('cti_customer');
        $class->setColumn('ctic_bonuspoints as bonuspoints', 'varchar', 50, array());
    }
}

class CTI_SuperManager extends CTI_Manager
{
    public static function initMetadata($class)
    {
        $class->setTableName('cti_supermanager');
        $class->setColumn('ctism_gosutitle as gosutitle', 'varchar', 50, array());
    }
}
