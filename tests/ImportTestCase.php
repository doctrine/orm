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
    public function testForeignKeySupport() {
        /**
        $this->dbh->query('CREATE TABLE album (
          id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
          title VARCHAR(100),
          artist VARCHAR(100)
        )');

        $this->dbh->query('CREATE TABLE track (
          id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
          album_id INTEGER,
          dsk INTEGER,
          posn INTEGER,
          song VARCHAR(255),
          FOREIGN KEY (album_id) REFERENCES album(id)
        )');


        $sql    = "PRAGMA table_info(track)";
        $sql    = "PRAGMA foreign_key_list(track)";
        $result = $this->dbh->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        */

    }

    /**
    public function testDatabaseConnectionIsReverseEngineeredToSchema()
    {

        $this->assertTrue($this->schema instanceof Doctrine_Schema);
        
        //table count should match
        $this->assertEqual(count($this->schema), count($this->tables));

    }           

    public function testBaseClassesAreWritten()
    {        
        //now lets match the original with the result
        foreach($this->tables as $name) 
        {
            $name = ucwords($name);
            $filename = $this->tmpdir.$name.$this->suffix.'.php';
            $this->assertTrue(file_exists($filename));
        }
    }           


    public function testNativeColumnDefinitionsAreTranslatedCorrectly()
    {        
        $transArr = array();
        

        $transArr['sqlite'] = array(
            // array(native type, native length, doctrine type, doctrine length),
            array('int', 11, 'int', 11),
            //array('varchar', 255, 'string', 255),
        );
        
        
        foreach ($transArr as $dbType => $colArr)
        {
        	foreach($colArr as $colDef)
            {
            	list($natType, $natLen, $expType, $expLen) = $colDef;
                list($resType, $resLen) = Doctrine_DataDict::getDoctrineType($natType, $natLen, $dbType);
                $this->assertEqual($resType, $expType);
                $this->assertEqual($resLen, $expLen);                
            }
        }
        
    }    

    public function testDoctrineRecordBaseClassesAreBuildCorrectly()
    {
        foreach($this->tables as $name) 
        {
            $name = ucwords($name);            
            $filename = $this->tmpdir.$name.$this->suffix.'.php';
            if(file_exists($filename))
            {
                require_once $filename;
                $obj = new $name.$this->suffix;

                list($oType, $oLength,) = $this->connection->getTable($name)->getColumns();
                list($rType, $rLength,) = $this->connection->getTable($name.$this->suffix)->getColumns();

                $this->assertEquals($rType, $oType);
                $this->assertEquals($rLength, $oLength);                
            }
        }        
    }    

          */

     // Gets the system temporary directory name
     // @return null on failure to resolve the system temp dir

    private function getTempDir()
    {
    	/**
        if(function_exists('sys_get_temp_dir')) {
            $tempdir = sys_get_temp_dir();
        } elseif (!empty($_ENV['TMP'])) {
            $tempdir = $_ENV['TMP'];
        } elseif (!empty($_ENV['TMPDIR'])) {
            $tempdir = $_ENV['TMPDIR'];
        } elseif (!empty($_ENV['TEMP'])) {
            $tempdir = $_ENV['TEMP'];
        } else {
        	//a little bit of chewing gum here will do the trick
            $tempdir = dirname(tempnam('/THIS_REALLY_SHOULD_NOT_EXISTS', 'na'));
        }

        if (empty($tempdir)) { return null; }

        $tempdir = rtrim($tempdir, '/');
        $tempdir .= DIRECTORY_SEPARATOR;

        if (is_writable($tempdir) == false) {
                return null;
        }
        $dir = tempnam($tempdir, 'doctrine_tests');

        @unlink($dir);
        @rmdir($dir);

        mkdir($dir);
        $dir .= DIRECTORY_SEPARATOR;

        return $dir;
        */
    }
}
