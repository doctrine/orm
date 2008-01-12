<?php


class Doctrine_Inheritance_SingleTable_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }

    public function prepareTables()
    {
        $this->tables[] = 'STI_User';
        $this->tables[] = 'STI_Manager';
        $this->tables[] = 'STI_Customer';
        $this->tables[] = 'STI_SuperManager';
        parent::prepareTables();
    }

    public function testMetadataSetup()
    { 
        $userTable = $this->conn->getTable('STI_User');
        $superManagerTable = $this->conn->getTable('STI_SuperManager');
        $managerTable = $this->conn->getTable('STI_Manager');
        $customerTable = $this->conn->getTable('STI_Customer');
        
        $this->assertTrue($superManagerTable === $userTable);
        $this->assertTrue($customerTable === $managerTable);
        $this->assertTrue($superManagerTable === $managerTable);
        $this->assertTrue($userTable === $customerTable);
        $this->assertEqual(7, count($userTable->getColumns()));
        
        $this->assertEqual(array(), $userTable->getOption('joinedParents'));
        $this->assertEqual(array(), $superManagerTable->getOption('joinedParents'));
        $this->assertEqual(array(), $managerTable->getOption('joinedParents'));
        $this->assertEqual(array(), $customerTable->getOption('joinedParents'));
        
        // check inheritance map
        $this->assertEqual(array(
                'STI_User' => array('type' => 1),
                'STI_Manager' => array('type' => 2),
                'STI_Customer' => array('type' => 3),
                'STI_SuperManager' => array('type' => 4)), $userTable->getOption('inheritanceMap'));
        
        //var_dump($superManagerTable->getComponentName());
    }
    
    public function testSave()
    {
        $manager = new STI_Manager();
        $manager->salary = 80000;
        $manager->name = 'John Smith';
        try {
            $manager->save();
            $this->assertEqual(1, $manager->id);
            $this->assertEqual(80000, $manager->salary);
            $this->assertEqual('John Smith', $manager->name);
            $this->assertEqual(2, $manager->type);
        } catch (Exception $e) {
            $this->fail("Saving record in single table inheritance failed: " . $e->getMessage());
        }
    }
    
    public function testQuery()
    {
        //$this->_createManager();
        $query = $this->conn->createQuery();
        $query->select("m.*")->from("STI_Manager m");
        //echo $query->getSql();
        //$managers = $query->execute();
        
    }
}


class STI_User extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setInheritanceType(Doctrine::INHERITANCETYPE_SINGLE_TABLE,
                array('STI_User' => array('type' => 1),
                      'STI_Manager' => array('type' => 2),
                      'STI_Customer' => array('type' => 3),
                      'STI_SuperManager' => array('type' => 4))
        );
        $this->setTableName('sti_entity');
        $this->hasColumn('sti_id as id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
        $this->hasColumn('sti_foo as foo', 'integer', 4);
        $this->hasColumn('sti_name as name', 'varchar', 50);
        $this->hasColumn('type', 'integer', 4);
    }
}

class STI_Manager extends STI_User 
{
    public function setTableDefinition()
    {
        $this->hasColumn('stim_salary as salary', 'varchar', 50, array());
    }
}

class STI_Customer extends STI_User
{
    public function setTableDefinition()
    {
        $this->hasColumn('stic_bonuspoints as bonuspoints', 'varchar', 50, array());
    }
}

class STI_SuperManager extends STI_Manager
{
    public function setTableDefinition()
    {
        $this->hasColumn('stism_gosutitle as gosutitle', 'varchar', 50, array());
    }
}
