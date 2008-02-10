<?php
/**
 * Base testcase class for all orm testcases.
 *
 */
class Doctrine_OrmTestCase extends Doctrine_TestCase
{
    private $_loadedFixtures = array();
    private static $_fixtures = array();
    private static $_exportedTables = array();
    
    protected function loadFixture($package, $name)
    {
        $uniqueName = $package . '/' . $name;
        
        if ( ! isset(self::$_fixtures[$uniqueName])) {
            // load fixture file
            $fixtureFile = 'fixtures' . DIRECTORY_SEPARATOR . 'orm' . DIRECTORY_SEPARATOR
                    . $package . DIRECTORY_SEPARATOR . $name . '.php';
            require $fixtureFile;
            self::$_fixtures[$uniqueName] = $fixture;
            
            // load model file
            $modelFile = 'models' . DIRECTORY_SEPARATOR . $package . DIRECTORY_SEPARATOR .
                    $fixture['model'] . '.php';
            require $modelFile;
        }
        
        $fixture = self::$_fixtures[$uniqueName];
        $this->_loadedFixtures[] = $fixture['model'];
        
        $conn = $this->sharedFixture['connection'];
        $classMetadata = $conn->getClassMetadata($fixture['model']);
        $tableName = $classMetadata->getTableName();
        
        if ( ! in_array($tableName, self::$_exportedTables)) {
            $conn->export->exportClasses(array($fixture['model']));
            self::$_exportedTables[] = $tableName;
        }
        
        foreach ($fixture['rows'] as $row) {
            $conn->insert($classMetadata, $row);
        }
    }
    
    protected function loadFixtures($package, array $names)
    {
        foreach ($names as $name) {
            $this->loadFixture($package, $name);
        }
    }
    
    protected function tearDown()
    {
        $conn = $this->sharedFixture['connection'];
        foreach (array_reverse($this->_loadedFixtures) as $model) {
            $conn->exec("DELETE FROM " . $conn->getClassMetadata($model)->getTableName());
        }
    }
    
    /*
    public function loadFixturesPackage($package, $models = array())
    {
        $packagePath = 'fixtures' . DIRECTORY_SEPARATOR . $package;

        if ( ! file_exists($packagePath)) {
            throw new Exception("Could not find fixtures package: $package.");
        }

        $modelsPath = $packagePath . DIRECTORY_SEPARATOR . 'models';
        $dataPath = $packagePath . DIRECTORY_SEPARATOR . 'data';
        
        Doctrine::loadModels($modelsPath);
        Doctrine::createTablesFromModels($modelsPath);
        
        $data = new Doctrine_Data();
        $data->importData($dataPath, 'yml', $models);
    }
    */
}