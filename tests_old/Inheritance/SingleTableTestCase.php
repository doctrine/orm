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
        $userClass = $this->conn->getClassMetadata('STI_User');
        $superManagerClass = $this->conn->getClassMetadata('STI_SuperManager');
        $managerClass = $this->conn->getClassMetadata('STI_Manager');
        $customerClass = $this->conn->getClassMetadata('STI_Customer');
        
        $this->assertEqual(4, count($userClass->getMappedColumns()));
        $this->assertEqual('sti_entity', $userClass->getTableName());
        $this->assertEqual('sti_entity', $managerClass->getTableName());
        
        // check inheritance map
        $this->assertEqual(array(1 => 'STI_User',
              2 => 'STI_Manager',
              3 => 'STI_Customer',
              4 => 'STI_SuperManager'), $userClass->getInheritanceOption('discriminatorMap'));
        
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
    public static function initMetadata($class)
    {
        $class->setInheritanceType(Doctrine::INHERITANCETYPE_SINGLE_TABLE, array(
                'discriminatorColumn' => 'type',
                'discriminatorMap' => array(
                      1 => 'STI_User',
                      2 => 'STI_Manager',
                      3 => 'STI_Customer',
                      4 => 'STI_SuperManager'))
        );
        $class->setSubclasses(array('STI_Manager', 'STI_Customer', 'STI_SuperManager'));
        $class->setTableName('sti_entity');
        $class->setColumn('sti_id as id', 'integer', 4, array('primary' => true, 'autoincrement' => true));
        $class->setColumn('sti_foo as foo', 'integer', 4);
        $class->setColumn('sti_name as name', 'varchar', 50);
        $class->setColumn('type', 'integer', 4);
    }
}

class STI_Manager extends STI_User 
{
    public static function initMetadata($class)
    {
        $class->setSubclasses(array('STI_SuperManager'));
        $class->setColumn('stim_salary as salary', 'varchar', 50, array());
    }
}

class STI_Customer extends STI_User
{
    public static function initMetadata($class)
    {
        $class->setColumn('stic_bonuspoints as bonuspoints', 'varchar', 50, array());
    }
}

class STI_SuperManager extends STI_Manager
{
    public static function initMetadata($class)
    {
        $class->setColumn('stism_gosutitle as gosutitle', 'varchar', 50, array());
    }
}
