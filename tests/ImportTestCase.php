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
class Doctrine_ImportTestCase extends Doctrine_UnitTestCase 
{
    private $tmpdir;
    
    private $suffix;
    
    private $schema;
       
    public function setUp()
    {
    	parent::setUp();

        //reading
        $reader = new Doctrine_Import_Reader_Db();
        $reader->setPdo($this->dbh);
        $this->schema = $reader->read();

        //and building
        $this->tmpdir = $this->getTempDir();
        $this->suffix = '__Base';

        $builder = new Doctrine_Import_Builder_BaseClass();
        $builder->setOutputPath($this->tmpdir);
        $builder->setFileSuffix($this->suffix.'.php');
        $builder->build($this->schema);
    }

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
        
        /* From SQLite column types */
        $transArr['sqlite'] = array(
            /* array(native type, native length, doctrine type, doctrine length), */
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

    public function tearDown()
    {
    	@unlink($this->tmpdir);
        @rmdir($this->tmpdir);
    }


    /**
     * Gets the system temporary directory name 
     * @return null on failure to resolve the system temp dir
     */
    private function getTempDir()
    {
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
    }
}