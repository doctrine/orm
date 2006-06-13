<?php
require_once("UnitTestCase.php");

class Doctrine_Cache_Query_SqliteTestCase extends Doctrine_UnitTestCase {
    public function setUp() {
        parent::setUp();
        $this->manager->setAttribute(Doctrine::ATTR_CACHE,Doctrine::CACHE_NONE);
        $dir = $this->session->getAttribute(Doctrine::ATTR_CACHE_DIR);

        if(file_exists($dir.DIRECTORY_SEPARATOR."stats.cache"))
            unlink($dir.DIRECTORY_SEPARATOR."stats.cache");

        $this->cache = new Doctrine_Cache_Query_Sqlite($this->objTable);
        $this->cache->deleteAll();
    }
    public function testConstructor() {
                                      	
    }
}
?>
