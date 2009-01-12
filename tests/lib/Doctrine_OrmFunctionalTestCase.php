<?php

#namespace Doctrine\Tests;

/**
 * Base testcase class for all orm testcases.
 *
 */
class Doctrine_OrmFunctionalTestCase extends Doctrine_OrmTestCase
{
    protected $_em;

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
     * WARNING: A single testcase should never load fixtures from different scenarios of
     * the same package as the concistency and uniqueness of keys is not guaranteed.
     *
     * @param string $package  The package name. Must be one of Doctrine's test model packages
     *                         (forum, cms or ecommerce).
     * @param string $scenario The fixture scenario. A model package can have many fixture
     *                         scenarios. Within a scenario all primary keys and foreign keys
     *                         of fixtures are consistent and unique.
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
        }
        
        $fixture = self::$_fixtures[$uniqueName];
        $this->_loadedFixtures[] = $fixture['table'];
        
        $conn = $this->sharedFixture['conn'];
        $tableName = $fixture['table'];
        
        if ( ! in_array($tableName, self::$_exportedTables)) {
            $conn->getSchemaManager()->exportClasses(array($fixture['model']));
            self::$_exportedTables[] = $tableName;
        }
        
        foreach ($fixture['rows'] as $row) {
            $conn->insert($tableName, $row);
        }
    }
    
    /**
     * Loads multiple fixtures of the same package and scenario.
     * This method must only be called from within the setUp() method of testcases.
     * The database will then be populated with fresh data of all loaded fixtures for each
     * test method.
     *
     * WARNING: A single testcase should never load fixtures from different scenarios of
     * the same package as the concistency and uniqueness of keys is not guaranteed.
     *
     * @param string $package  The package name. Must be one of Doctrine's test model packages
     *                         (forum, cms or ecommerce).
     * @param string $scenario The fixture scenario. A model package can have many fixture
     *                         scenarios. Within a scenario all primary keys and foreign keys
     *                         of fixtures are consistent and unique.
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
        $conn = $this->sharedFixture['conn'];
        foreach (array_reverse($this->_loadedFixtures) as $table) {
            $conn->exec("DELETE FROM " . $table);
        }
        $this->_em->clear();
    }

    protected function setUp()
    {
        if ( ! isset($this->sharedFixture['conn'])) {
            echo " --- CREATE CONNECTION ----";
            $this->sharedFixture['conn'] = Doctrine_TestUtil::getConnection();
        }
        if ( ! $this->_em) {
            $this->_em = $this->_getEntityManager();
        }
    }

    protected function _getEntityManager($config = null, $eventManager = null) {
        $config = new Doctrine_ORM_Configuration();
        $eventManager = new Doctrine_Common_EventManager();
        $conn = $this->sharedFixture['conn'];
        return Doctrine_ORM_EntityManager::create($conn, 'em', $config, $eventManager);
    }
}