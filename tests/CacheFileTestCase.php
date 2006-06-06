<?php
require_once("UnitTestCase.php");

class Doctrine_Cache_FileTestCase extends Doctrine_UnitTestCase {
    public function setUp() {
        parent::setUp();
        $this->manager->setAttribute(Doctrine::ATTR_CACHE, Doctrine::CACHE_FILE);
    }
    public function testStore() {
        $this->cache->store($this->old);
        $this->assertTrue($this->cache->exists(4));

        $record = $this->cache->fetch(4);
        $this->assertTrue($record instanceof Doctrine_Record);
        $this->assertTrue($record->getID() == $this->old->getID());
        
        $this->assertTrue($this->cache->getTable() == $this->objTable);
    }
    public function testGetFetched() {
        $this->assertTrue(is_array($this->cache->getFetched()));
    }
    public function testGetFileName() {
        $this->assertEqual($this->manager->getRoot().DIRECTORY_SEPARATOR."cache".DIRECTORY_SEPARATOR."entity".DIRECTORY_SEPARATOR."4.cache", $this->cache->getFileName(4));
    }
    public function testGetStats() {
        $this->assertTrue(gettype($this->cache->getStats()) == "array");
    }
    public function testDestructor() {
        $this->objTable->setAttribute(Doctrine::ATTR_CACHE_TTL,1);
        $this->objTable->setAttribute(Doctrine::ATTR_CACHE_SIZE,5);
        $this->cache->__destruct();
        $this->assertTrue($this->cache->count() == 5);

        $this->objTable->setAttribute(Doctrine::ATTR_CACHE_TTL,1);
        $this->objTable->setAttribute(Doctrine::ATTR_CACHE_SIZE,1);
        $this->cache->__destruct();
        $this->assertTrue($this->cache->count() == 1);

    }
    public function testDeleteMultiple() {
        $this->objTable->find(5);
        $this->objTable->find(6);
        
        $deleted = $this->cache->deleteMultiple(array(5,6));
        $this->assertTrue($deleted == 2);
    }
    public function testDeleteAll() {
        $this->cache->deleteAll();
        $this->assertTrue($this->cache->count() == 0);
    }
    public function testExists() {
        $this->assertFalse($this->cache->exists(313213123));
        $this->assertTrue($this->cache->exists(4));
    }
    public function testGetFactory() {
        $this->assertTrue($this->cache->getTable() == $this->objTable);
    }

}
?>
