<?php
/**
 * Base testcase class for all orm testcases.
 *
 */
class Doctrine_OrmTestCase extends Doctrine_TestCase
{
    /**
     * The currently loaded model names of the fixtures for the testcase.
     */
    private $_loadedFixtures = array();
    
    /**
     * All loaded fixtures during test execution. Common fixture cache.
     */
    private static $_fixtures = array();
    
    /**
     * The names of all tables that were already exported. Each table is exported
     * only once. Then it's just filled & erased for each testmethod in a testcase
     * that uses one or more fixtures.
     */
    private static $_exportedTables = array();
    
    /**
     * Loads a data fixture into the database. This method must only be called
     * from within the setUp() method of testcases. The database will then be
     * populated with fresh data of all loaded fixtures for each test method.
     *
     * @param string $package  The package name. Must be one of Doctrine's test model packages
     *                         (forum, cms or ecommerce).
     * @param string $name     The name of the fixture to load from the specified package.
     */
    protected function loadFixture($package, $scenario, $name)
    {
        $uniqueName = $package . '/' . $scenario . '/' . $name;
        
        if ( ! isset(self::$_fixtures[$uniqueName])) {
            // load fixture file
            $fixtureFile = 'fixtures'
                    . DIRECTORY_SEPARATOR . $package
                    . DIRECTORY_SEPARATOR . $scenario
                    . DIRECTORY_SEPARATOR . $name
                    . '.php';
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
    
    /**
     * Loads multiple fixtures of the same package.
     * This method must only be called from within the setUp() method of testcases.
     * The database will then be populated with fresh data of all loaded fixtures for each
     * test method.
     *
     * @param string $package  The package name. Must be one of Doctrine's test model packages
     *                         (forum, cms or ecommerce).
     * @param array $names     The names of the fixtures to load from the specified package.
     */
    protected function loadFixtures($package, $scenario, array $names)
    {
        foreach ($names as $name) {
            $this->loadFixture($package, $scenario, $name);
        }
    }
    
    /**
     * Sweeps the database tables of all used fixtures.
     */
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