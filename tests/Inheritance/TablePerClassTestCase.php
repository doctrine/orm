<?php

/**
 * Concrete Table Inheritance mapping tests.
 */
class Doctrine_Inheritance_TablePerClass_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }
    
    public function setUp()
    {
        parent::setUp();
        $this->prepareTables();
    }

    public function prepareTables()
    {
        $this->tables[] = 'CCTI_User';
        $this->tables[] = 'CCTI_Manager';
        $this->tables[] = 'CCTI_Customer';
        $this->tables[] = 'CCTI_SuperManager';
        parent::prepareTables();
    }

    public function testMetadataTableSetup()
    { 
        $supMngrTable = $this->conn->getClassMetadata('CCTI_SuperManager');
        $usrTable = $this->conn->getClassMetadata('CCTI_User');
        $mngrTable = $this->conn->getClassMetadata('CCTI_Manager');
        $customerTable = $this->conn->getClassMetadata('CCTI_Customer');
        
        $this->assertEqual(3, count($usrTable->getColumns()));
        $this->assertEqual(4, count($mngrTable->getColumns()));
        $this->assertEqual(4, count($customerTable->getColumns()));
        $this->assertEqual(5, count($supMngrTable->getColumns()));
        
        $this->assertEqual('ccti_user', $usrTable->getTableName());
        $this->assertEqual('ccti_manager', $mngrTable->getTableName());
        $this->assertEqual('ccti_customer', $customerTable->getTableName());
        $this->assertEqual('ccti_supermanager', $supMngrTable->getTableName());
        
        //var_dump($mngrTable->getColumns());
    }
    
    public function testSave()
    {
        $manager = new CCTI_Manager();
        $manager->salary = 80000;
        $manager->name = 'John Smith';
        try {
            $manager->save();
            $this->assertEqual(1, $manager->id);
            $this->assertEqual(80000, $manager->salary);
            $this->assertEqual('John Smith', $manager->name);
        } catch (Exception $e) {
            $this->fail("Saving record in concrete table inheritance failed: " . $e->getMessage());
        }
    }
    
    public function testQuery()
    {
        //$manager = $this->conn->query("FROM CCTI_Manager")->getFirst();
        //var_dump($manager);
    }
}


class CCTI_User extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setInheritanceType(Doctrine::INHERITANCETYPE_TABLE_PER_CLASS);
        $class->setTableName('ccti_user');
        $class->setSubclasses(array('CCTI_Manager', 'CCTI_Customer', 'CCTI_SuperManager'));
        $class->setColumn('ccti_id as id', 'integer', 4, array ('primary' => true, 'autoincrement' => true));
        $class->setColumn('ccti_foo as foo', 'integer', 4);
        $class->setColumn('ccti_name as name', 'varchar', 50, array ());
    }
}

class CCTI_Manager extends CCTI_User 
{
    public static function initMetadata($class)
    {
        $class->setTableName('ccti_manager');
        $class->setSubclasses(array('CCTI_SuperManager'));
        $class->setColumn('ccti_salary as salary', 'varchar', 50, array());
    }
}

class CCTI_Customer extends CCTI_User
{
    public static function initMetadata($class)
    {
        $class->setTableName('ccti_customer');
        $class->setColumn('ccti_bonuspoints as bonuspoints', 'varchar', 50, array());
    }
}

class CCTI_SuperManager extends CCTI_Manager
{
    public static function initMetadata($class)
    {
        $class->setTableName('ccti_supermanager');
        $class->setColumn('ccti_gosutitle as gosutitle', 'varchar', 50, array());
    }
}
