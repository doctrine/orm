<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Tools\Setup;

require_once __DIR__ . '/../../TestInit.php';

class SetupTest extends \Doctrine\Tests\OrmTestCase
{
    private $originalAutoloaderCount;
    private $originalIncludePath;
    
    public function setUp()
    {
        if (strpos(\Doctrine\ORM\Version::VERSION, "DEV") === false) {
            $this->markTestSkipped("Test only runs in a dev-installation from Github");
        }
        
        $this->originalAutoloaderCount = count(spl_autoload_functions());
        $this->originalIncludePath = get_include_path();
    }
    
    public function testGitAutoload()
    {        
        Setup::registerAutoloadGit(__DIR__ . "/../../../../../");
        
        $this->assertEquals($this->originalAutoloaderCount + 4, count(spl_autoload_functions()));
    }
    
    public function testPEARAutoload()
    {
        set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . "/../../../../../lib/vendor/doctrine-common/lib");
        
        Setup::registerAutoloadPEAR();
        
        $this->assertEquals($this->originalAutoloaderCount + 2, count(spl_autoload_functions()));
    }
    
    public function testDirectoryAutoload()
    {
        Setup::registerAutoloadDirectory(__DIR__ . "/../../../../../lib/vendor/doctrine-common/lib");
        
        $this->assertEquals($this->originalAutoloaderCount + 2, count(spl_autoload_functions()));
    }
    
    public function testAnnotationConfiguration()
    {
        
    }
    
    public function tearDown()
    {
        set_include_path($this->originalIncludePath);
        $loaders = spl_autoload_functions();
        for ($i = 0; $i < count($loaders); $i++) {
            if ($i > $this->originalAutoloaderCount+1) {
                spl_autoload_unregister($loaders[$i]);
            }
        }
    }
}