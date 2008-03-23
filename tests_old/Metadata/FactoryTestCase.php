<?php

class Doctrine_Metadata_Factory_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }

    public function prepareTables()
    {
        $this->tables[] = 'Metadata_User';
        $this->tables[] = 'Metadata_Manager';
        $this->tables[] = 'Metadata_Customer';
        $this->tables[] = 'Metadata_SuperManager';
        parent::prepareTables();   
    }
    
    public function setUp()
    {
        parent::setUp();
        $this->prepareTables();
    }

    public function testMetadataSetupOnClassTableInheritanceHierarchy()
    {        
        $userClass = $this->conn->getClassMetadata('Metadata_User');
        $this->assertTrue($userClass instanceof Doctrine_ClassMetadata);
        $this->assertEqual('cti_user', $userClass->getTableName());
        $this->assertEqual(4, count($userClass->getMappedColumns()));
        $this->assertIdentical(array(), $userClass->getParentClasses());
        $this->assertEqual('type', $userClass->getInheritanceOption('discriminatorColumn'));
        $this->assertIdentical(array(
              1 => 'CTI_User',
              2 => 'CTI_Manager',
              3 => 'CTI_Customer',
              4 => 'CTI_SuperManager'), $userClass->getInheritanceOption('discriminatorMap'));
        
        
        $managerClass = $this->conn->getMetadata('Metadata_Manager');
        $this->assertTrue($managerClass instanceof Doctrine_ClassMetadata);
        $this->assertIdentical(array('Metadata_User'), $managerClass->getParentClasses());
        $this->assertEqual('cti_manager', $managerClass->getTableName());
        $this->assertEqual(5, count($managerClass->getMappedColumns()));
        $this->assertEqual('type', $managerClass->getInheritanceOption('discriminatorColumn'));
        $this->assertIdentical(array(
              1 => 'CTI_User',
              2 => 'CTI_Manager',
              3 => 'CTI_Customer',
              4 => 'CTI_SuperManager'), $managerClass->getInheritanceOption('discriminatorMap'));
        
        
        $suManagerClass = $this->conn->getMetadata('Metadata_SuperManager');
        $this->assertTrue($suManagerClass instanceof Doctrine_ClassMetadata);
        $this->assertIdentical(array('Metadata_Manager', 'Metadata_User'), $suManagerClass->getParentClasses());
        $this->assertEqual('cti_supermanager', $suManagerClass->getTableName());
        $this->assertEqual(6, count($suManagerClass->getMappedColumns()));
        $this->assertEqual('type', $suManagerClass->getInheritanceOption('discriminatorColumn'));
        $this->assertIdentical(array(
              1 => 'CTI_User',
              2 => 'CTI_Manager',
              3 => 'CTI_Customer',
              4 => 'CTI_SuperManager'), $suManagerClass->getInheritanceOption('discriminatorMap'));
        
        //var_dump($suManagerClass->getColumns());
    }
    
    public function testExportableFormatOfClassInClassTableInheritanceHierarchy()
    {
        $userClass = $this->conn->getClassMetadata('Metadata_User');
        $userClassExportableFormat = $userClass->getExportableFormat();
        $this->assertEqual(4, count($userClassExportableFormat['columns']));
        $this->assertTrue(isset($userClassExportableFormat['columns']['cti_id']));
        $this->assertTrue(isset($userClassExportableFormat['columns']['cti_id']['primary']));
        $this->assertTrue(isset($userClassExportableFormat['columns']['cti_id']['autoincrement']));
        $this->assertTrue(isset($userClassExportableFormat['columns']['cti_foo']));
        $this->assertTrue(isset($userClassExportableFormat['columns']['cti_name']));
        $this->assertTrue(isset($userClassExportableFormat['columns']['type']));
        
        $managerClass = $this->conn->getClassMetadata('Metadata_Manager');
        $managerClassExportableFormat = $managerClass->getExportableFormat();
        $this->assertEqual(2, count($managerClassExportableFormat['columns']));
        $this->assertTrue(isset($managerClassExportableFormat['columns']['cti_id']));
        $this->assertTrue(isset($managerClassExportableFormat['columns']['cti_id']['primary']));
        $this->assertFalse(isset($managerClassExportableFormat['columns']['cti_id']['autoincrement']));
        
        $customerClass = $this->conn->getClassMetadata('Metadata_Customer');
        $customerClassExportableFormat = $customerClass->getExportableFormat();
        $this->assertEqual(2, count($customerClassExportableFormat['columns']));
        $this->assertTrue(isset($customerClassExportableFormat['columns']['cti_id']));
        $this->assertTrue(isset($customerClassExportableFormat['columns']['cti_id']['primary']));
        $this->assertFalse(isset($customerClassExportableFormat['columns']['cti_id']['autoincrement']));
        
        $superManagerClass = $this->conn->getClassMetadata('Metadata_SuperManager');
        $superManagerClassExportableFormat = $superManagerClass->getExportableFormat();
        $this->assertEqual(2, count($superManagerClassExportableFormat['columns']));
        $this->assertTrue(isset($superManagerClassExportableFormat['columns']['cti_id']));
        $this->assertTrue(isset($superManagerClassExportableFormat['columns']['cti_id']['primary']));
        $this->assertFalse(isset($superManagerClassExportableFormat['columns']['cti_id']['autoincrement']));
    }
    
    public function testMetadataSetupOnSingleTableInheritanceHierarchy()
    {        
        $userClass = $this->conn->getClassMetadata('Metadata_STI_User');
        $this->assertTrue($userClass instanceof Doctrine_ClassMetadata);
        $this->assertEqual('cti_user', $userClass->getTableName());
        $this->assertEqual(4, count($userClass->getMappedColumns()));
        $this->assertIdentical(array(), $userClass->getParentClasses());
        $this->assertEqual('type', $userClass->getInheritanceOption('discriminatorColumn'));
        $this->assertIdentical(array(
              1 => 'CTI_User',
              2 => 'CTI_Manager',
              3 => 'CTI_Customer',
              4 => 'CTI_SuperManager'), $userClass->getInheritanceOption('discriminatorMap'));
        
        $managerClass = $this->conn->getClassMetadata('Metadata_STI_Manager');
        $this->assertTrue($managerClass instanceof Doctrine_ClassMetadata);
        $this->assertIdentical(array('Metadata_STI_User'), $managerClass->getParentClasses());
        $this->assertEqual('cti_user', $managerClass->getTableName());
        $this->assertEqual(5, count($managerClass->getMappedColumns()));
        $this->assertEqual('type', $managerClass->getInheritanceOption('discriminatorColumn'));
        $this->assertIdentical(array(
              1 => 'CTI_User',
              2 => 'CTI_Manager',
              3 => 'CTI_Customer',
              4 => 'CTI_SuperManager'), $managerClass->getInheritanceOption('discriminatorMap'));
        
        
        $suManagerClass = $this->conn->getClassMetadata('Metadata_STI_SuperManager');
        $this->assertTrue($suManagerClass instanceof Doctrine_ClassMetadata);
        $this->assertIdentical(array('Metadata_STI_Manager', 'Metadata_STI_User'), $suManagerClass->getParentClasses());
        $this->assertEqual('cti_user', $suManagerClass->getTableName());
        $this->assertEqual(6, count($suManagerClass->getMappedColumns()));
        $this->assertEqual('type', $suManagerClass->getInheritanceOption('discriminatorColumn'));
        $this->assertIdentical(array(
              1 => 'CTI_User',
              2 => 'CTI_Manager',
              3 => 'CTI_Customer',
              4 => 'CTI_SuperManager'), $suManagerClass->getInheritanceOption('discriminatorMap'));
        
        //var_dump($suManagerClass->getColumns());
    }
}


class Metadata_User extends Doctrine_Record
{    
    public static function initMetadata(Doctrine_ClassMetadata $class)
    {
        $class->setTableName('cti_user');
        $class->setInheritanceType(Doctrine::INHERITANCE_TYPE_JOINED,
                array('discriminatorColumn' => 'type',
                      'discriminatorMap' => array(
                          1 => 'CTI_User',
                          2 => 'CTI_Manager',
                          3 => 'CTI_Customer',
                          4 => 'CTI_SuperManager')
                )
        );
        $class->setSubclasses(array('Metadata_Manager', 'Metadata_Customer', 'Metadata_SuperManager'));
        $class->mapColumn('cti_id as id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
        $class->mapColumn('cti_foo as foo', 'integer', 4);
        $class->mapColumn('cti_name as name', 'string', 50);
        $class->mapColumn('type', 'integer', 1);
        
        //$class->setNamedQuery('findByName', 'SELECT u.* FROM User u WHERE u.name = ?');
    }
}

class Metadata_Manager extends Metadata_User 
{
    public static function initMetadata(Doctrine_ClassMetadata $class)
    {
        $class->setTableName('cti_manager');
        $class->setSubclasses(array('Metadata_SuperManager'));
        $class->mapColumn('ctim_salary as salary', 'varchar', 50, array());
    }
}

class Metadata_Customer extends Metadata_User
{
    public static function initMetadata(Doctrine_ClassMetadata $class)
    {
        $class->setTableName('cti_customer');
        $class->mapColumn('ctic_bonuspoints as bonuspoints', 'varchar', 50, array());
    }
}

class Metadata_SuperManager extends Metadata_Manager
{
    public static function initMetadata(Doctrine_ClassMetadata $class)
    {
        $class->setTableName('cti_supermanager');
        $class->mapColumn('ctism_gosutitle as gosutitle', 'varchar', 50, array());
    }
}



class Metadata_STI_User extends Doctrine_Record
{    
    public static function initMetadata($class)
    {
        $class->setTableName('cti_user');
        $class->setInheritanceType(Doctrine::INHERITANCE_TYPE_SINGLE_TABLE,
                array('discriminatorColumn' => 'type',
                      'discriminatorMap' => array(
                          1 => 'CTI_User',
                          2 => 'CTI_Manager',
                          3 => 'CTI_Customer',
                          4 => 'CTI_SuperManager')
                )
        );
        $class->setSubclasses(array('Metadata_STI_Manager', 'Metadata_STI_Customer', 'Metadata_STI_SuperManager'));
        $class->mapColumn('cti_id as id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
        $class->mapColumn('cti_foo as foo', 'integer', 4);
        $class->mapColumn('cti_name as name', 'string', 50);
        $class->mapColumn('type', 'integer', 1);
        
        //$class->setNamedQuery('findByName', 'SELECT u.* FROM User u WHERE u.name = ?');
    }
}

class Metadata_STI_Manager extends Metadata_STI_User 
{
    public static function initMetadata($class)
    {
        $class->setTableName('cti_manager');
        $class->setSubclasses(array('Metadata_STI_SuperManager'));
        $class->mapColumn('ctim_salary as salary', 'varchar', 50, array());
    }
}

class Metadata_STI_Customer extends Metadata_STI_User
{
    public static function initMetadata($class)
    {
        $class->setTableName('cti_customer');
        $class->mapColumn('ctic_bonuspoints as bonuspoints', 'varchar', 50, array());
    }
}

class Metadata_STI_SuperManager extends Metadata_STI_Manager
{
    public static function initMetadata($class)
    {
        $class->setTableName('cti_supermanager');
        $class->mapColumn('ctism_gosutitle as gosutitle', 'varchar', 50, array());
    }
}

