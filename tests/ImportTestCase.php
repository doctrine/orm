<?php
/**
 * ImportTestCase.php - 24.8.2006 2.37.14
 * 
 * Note that some shortcuts maybe used here 
 * i.e. these tests depends that exporting is working
 *
 * @author Jukka Hassinen <Jukka.Hassinen@BrainAlliance.com>
 * @version $Id$
 * @package Doctrine
 */
class Doctrine_Import_TestCase extends Doctrine_UnitTestCase {
    private $tmpdir;
    
    private $suffix;
    
    private $schema;
       
    public function prepareTables() { }
    public function prepareData() { }

    public function setUp()
    {
    	parent::setUp();

        //reading
        $reader = new Doctrine_Import_Reader_Db();
        $reader->setPdo($this->dbh);
        $this->schema = $reader->read();
    }
    
    public function testBadImport() {
        $builder = new Doctrine_Import_Builder();
        
        try { 
            $builder->buildRecord(new Doctrine_Schema_Table());

            $this->fail();
        } catch(Doctrine_Import_Builder_Exception $e) {
            $this->pass();
        }

    }

    public function testImportTable() {
        $definition = array('name' => 'import_test');

        $table = new Doctrine_Schema_Table($definition);
        $def     = array('name' => 'name',
                         'type' => 'string',
                         'length' => 20,
                         'default' => 'someone');

        $table->addColumn(new Doctrine_Schema_Column($def));

        $def     = array('name' => 'created',
                         'type' => 'integer',
                         'notnull' => true);

        $table->addColumn(new Doctrine_Schema_Column($def));

        $builder = new Doctrine_Import_Builder();

        $builder->setTargetPath('tmp');
        try {
            $builder->buildRecord($table);

            $this->pass();
        } catch(Doctrine_Import_Builder_Exception $e) {
            $this->fail();
        }
        $this->assertTrue(file_exists('tmp' . DIRECTORY_SEPARATOR . 'ImportTest.php'));
    }
    public function testImportedComponent() {
        require_once('tmp' . DIRECTORY_SEPARATOR . 'ImportTest.php');

        $r = new ImportTest();
        $columns = $r->getTable()->getColumns();

        // id column is auto-created
        
        $this->assertEqual($columns['id'][0], 'integer');
        $this->assertEqual($columns['id'][1], 20);

        $this->assertEqual($columns['name'][0], 'string');
        $this->assertEqual($columns['name'][1], 20);
        
        $this->assertEqual($columns['created'][0], 'integer');

        unlink('tmp' . DIRECTORY_SEPARATOR . 'ImportTest.php');
    }
}
