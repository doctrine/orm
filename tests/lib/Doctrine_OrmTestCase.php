<?php
/**
 * Base testcase class for all orm testcases.
 *
 * Provides the testcases with fixture support and other orm related capabilities.
 */
class Doctrine_OrmTestCase extends Doctrine_TestCase
{
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
}