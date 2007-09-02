<?php
/**
 * SchemaTestCase.php - 24.8.2006 1.18.54
 *
 * @author Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @version $Id$
 * @package Doctrine
 */
class Doctrine_Schema_TestCase extends Doctrine_UnitTestCase 
{ 

	public function testEverySchemaObjectIsThrowingExceptionOnNonPropertyAssignment()
    {
    	$isException = false;     
    	$obj = new Doctrine_Schema();
    	try {
    		$obj->no_such_property = 'this should throw an exception';
    	} catch (Doctrine_Schema_Exception $e)
        {
        	$isException = true;
        }
        $this->assertTrue($isException);

        $isException = false;     
        $obj = new Doctrine_Schema_Database();
        try {
            $obj->no_such_property = 'this should throw an exception';
        } catch (Doctrine_Schema_Exception $e)
        {
            $isException = true;
        }
        $this->assertTrue($isException);

        $isException = false;     
        $obj = new Doctrine_Schema_Table();
        try {
            $obj->no_such_property = 'this should throw an exception';
        } catch (Doctrine_Schema_Exception $e)
        {
            $isException = true;
        }
        $this->assertTrue($isException);

        $isException = false;     
        $obj = new Doctrine_Schema_Column();
        try {
            $obj->no_such_property = 'this should throw an exception';
        } catch (Doctrine_Schema_Exception $e)
        {
            $isException = true;
        }
        $this->assertTrue($isException);

        $isException = false;     
        $obj = new Doctrine_Schema_Relation();
        try {
            $obj->no_such_property = 'this should throw an exception';
        } catch (Doctrine_Schema_Exception $e)
        {
            $isException = true;
        }
        $this->assertTrue($isException);
    }

	public function testEverySchemaObjectIsThrowingExceptionOnNonPropertyAccess()
    {
        $isException = false;     
        $obj = new Doctrine_Schema();
        try {
            $value = $obj->no_such_property;
        } catch (Doctrine_Schema_Exception $e)
        {
            $isException = true;
        }
        $this->assertTrue($isException);      

        $isException = false;     
        $obj = new Doctrine_Schema_Database();
        try {
            $value = $obj->no_such_property;
        } catch (Doctrine_Schema_Exception $e)
        {
            $isException = true;
        }
        $this->assertTrue($isException);      

        $isException = false;     
        $obj = new Doctrine_Schema_Table();
        try {
            $value = $obj->no_such_property;
        } catch (Doctrine_Schema_Exception $e)
        {
            $isException = true;
        }
        $this->assertTrue($isException);      

        $isException = false;     
        $obj = new Doctrine_Schema_Column();
        try {
            $value = $obj->no_such_property;
        } catch (Doctrine_Schema_Exception $e)
        {
            $isException = true;
        }
        $this->assertTrue($isException);      

        $isException = false;     
        $obj = new Doctrine_Schema_Relation();
        try {
            $value = $obj->no_such_property;
        } catch (Doctrine_Schema_Exception $e)
        {
            $isException = true;
        }
        $this->assertTrue($isException);      
    }

    public function testSchemaDatabasePropertiesAreAssignableAndAccessible()
    {
        $obj = new Doctrine_Schema_Database();
        $vars = array(
            'name'    => 'mydatabase', 
            'type'    => 'MySQL', 
            'version' => '5.0', 
            'engine'  => 'InnoDB', 
            'charset' => 'UTF-8'
            );
            
        foreach ($vars as $key => $val)
        {
            $obj->$key = $val;
            $this->assertEqual($obj->$key, $val);
        }
        

    }

    public function testSchemaTablePropertiesAreAssignableAndAccessible()
    {
        $obj = new Doctrine_Schema_Table();
        $vars = array(
            'name'    => 'User', 
            'check'    => '(col1 < col2)', 
            'charset'  => 'UTF-8', 
            'description' => 'User data'
            );
            
        foreach ($vars as $key => $val)
        {
            $obj->$key = $val;
            $this->assertEqual($obj->$key, $val);
        }
    }

    public function testSchemaColumnPropertiesAreAssignableAndAccessible()
    {
        $obj = new Doctrine_Schema_Column();
        $vars = array(
            'name'          => 'id', 
            'type'          => 'int', 
            'length'        => 10, 
            'autoinc'       => true,
            'default'       => null, 
            'notnull'       => true,
            // 'description'   => 'user id',
            // 'check'         => 'id > 0',
            // 'charset'       => 'UTF-8'
            );

        foreach ($vars as $key => $val)
        {
            $obj->$key = $val;
            $this->assertEqual($obj->$key, $val);
        }
    }

    public function testSchemaDatabaseIsCloneable()
    {
    }

    
    public function testSchemaIsTraversable()
    {
    	/* @todo complete 
        
    	$schema = new Doctrine_Schema();

        foreach($schema as $key => $db)
        {
            $this->assertEqual($db->name, $key);
        	foreach($db as $key => $table)
            {
                $this->assertEqual($table->name, $key);
            	foreach($table as $key => $col)
                {
                	$this->assertEqual($col->name, $key);
                }
            }
        }        
         */
    }
}
