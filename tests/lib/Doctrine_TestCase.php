<?php
class Doctrine_TestCase extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $pdo = new PDO('sqlite::memory:');
        $this->sharedFixture = $this->loadConnection($pdo, 'sqlite_memory');
    }

    public function loadConnection($conn, $name)
    {
        return Doctrine_Manager::connection($conn, $name);
    }

    public function loadFixturesPackage($package, $models = array())
    {
        $packagePath = 'fixtures' . DIRECTORY_SEPARATOR . $package;

        if ( ! file_exists($packagePath)) {
            throw new Exception('Could not find fixtures package: "' . $package . '"');
        }

        $modelsPath = $packagePath . DIRECTORY_SEPARATOR . 'models';
        $dataPath = $packagePath . DIRECTORY_SEPARATOR . 'data';
        
        Doctrine::loadModels($modelsPath);
        Doctrine::createTablesFromModels($modelsPath);
        
        $data = new Doctrine_Data();
        $data->importData($dataPath, 'yml', $models);
    }

    public function tearDown()
    {
        Doctrine_Manager::getInstance()->getConnection('sqlite_memory')->close();

        $this->sharedFixture = NULL;
    }
}