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
    public function testStore() {

        $this->cache->store("SELECT * FROM user", array(array('name' => 'Jack Daniels')), 60);
        $this->assertEqual($this->cache->count(), 1);

        $this->cache->store("SELECT * FROM group", array(array('name' => 'Drinkers club')), 60);
        
        $md5 = md5("SELECT * FROM user");
        $result = $this->cache->fetch($md5);
        $this->assertEqual($result, array(array('name' => 'Jack Daniels')));

        $md5 = md5("SELECT * FROM group");
        $result = $this->cache->fetch($md5);
        $this->assertEqual($result, array(array('name' => 'Drinkers club')));

        $this->assertEqual($this->cache->count(), 2);
        
        $this->cache->delete($md5);
        $this->assertEqual($this->cache->count(), 1);
        
        $this->cache->deleteAll();
        $this->assertEqual($this->cache->count(), 0);
    }
}
?>
