<?php
require_once("UnitTestCase.php");

class Doctrine_Cache_SqliteTestCase extends Doctrine_UnitTestCase {
    public function setUp() {
        parent::setUp();
        $this->manager->setAttribute(Doctrine::ATTR_CACHE,Doctrine::CACHE_NONE);
        $dir = $this->connection->getAttribute(Doctrine::ATTR_CACHE_DIR);

        if(file_exists($dir.DIRECTORY_SEPARATOR."stats.cache"))
            unlink($dir.DIRECTORY_SEPARATOR."stats.cache");

        $this->cache = new Doctrine_Cache_Sqlite($this->objTable);
        $this->cache->deleteAll();
    }

    public function testStore() {
        // does not store proxy objects
        $this->assertFalse($this->cache->store($this->objTable->getProxy(4)));
        
        $this->assertTrue($this->cache->store($this->objTable->find(4)));

        $record = $this->cache->fetch(4);
        $this->assertTrue($record instanceof Doctrine_Entity);

        foreach($this->old as $name => $value) {
            $this->assertEqual($record->get($name), $value);
        }
        $this->assertEqual($record->obtainIdentifier(), $this->old->obtainIdentifier());

    }
    public function testFetchMultiple() {
        $this->assertFalse($this->cache->fetchMultiple(array(5,6)));
        $this->cache->store($this->objTable->find(5));

        $array = $this->cache->fetchMultiple(array(5,6));
        $this->assertEqual(gettype($array), "array");
        $this->assertEqual(count($array), 1);
        $this->assertTrue($array[0] instanceof Doctrine_Entity);
    }
    public function testDeleteMultiple() {
        $this->assertEqual($this->cache->deleteMultiple(array()),0);
        $this->cache->store($this->objTable->find(5));
        $this->cache->store($this->objTable->find(6));

        $count = $this->cache->deleteMultiple(array(5,6));

        $this->assertEqual($count,2);
        $this->cache->store($this->objTable->find(6));
        $count = $this->cache->deleteMultiple(array(5,6));
        $this->assertEqual($count,1);
    }
    public function testDelete() {
        $this->cache->store($this->objTable->find(5));
        $this->assertTrue($this->cache->fetch(5) instanceof Doctrine_Entity);

        $this->assertEqual($this->cache->delete(5),true);
        $this->assertFalse($this->cache->fetch(5));

        $this->assertFalse($this->cache->delete(0));
    }

    public function testFetch() {
        $this->assertFalse($this->cache->fetch(3));

    }
    public function testCount() {
        $this->assertEqual($this->cache->count(), 0);
        $this->cache->store($this->objTable->find(5));
        $this->assertEqual($this->cache->count(), 1);
    }
    public function testSaveStats() {
        $this->assertFalse($this->cache->saveStats());
        $this->cache->store($this->objTable->find(5));
        $this->cache->store($this->objTable->find(6));
        $this->cache->store($this->objTable->find(7));
        $this->cache->fetchMultiple(array(5,6,7));

        $this->assertTrue($this->cache->saveStats());
        $this->assertTrue(gettype($this->cache->getStats()), "array");
        $this->assertEqual($this->cache->getStats(),array(5 => 1, 6 => 1, 7 => 1));

        $this->cache->fetchMultiple(array(5,6,7));
        $this->cache->fetch(5);
        $this->cache->fetch(7);
        $this->assertTrue($this->cache->saveStats());
        $this->assertEqual($this->cache->getStats(),array(5 => 3, 6 => 2, 7 => 3));
    }
    public function testClean() {
        $this->cache->store($this->objTable->find(4));
        $this->cache->store($this->objTable->find(5));
        $this->cache->store($this->objTable->find(6));
        $this->cache->store($this->objTable->find(7));
        $this->cache->store($this->objTable->find(8));
        $this->cache->store($this->objTable->find(9));
        $this->assertEqual($this->cache->count(), 6);
        $this->cache->fetch(5);
        $this->cache->fetch(7);
        $this->cache->fetchMultiple(array(5,6,7));
        $this->cache->fetchMultiple(array(5,6,7));
        $this->cache->fetchMultiple(array(5,6,7));
        $this->cache->fetchMultiple(array(4,5,6,7,8,9));
        $this->assertTrue($this->cache->saveStats());
        
        $this->manager->setAttribute(Doctrine::ATTR_CACHE_SIZE, 3);
        $this->assertEqual($this->cache->clean(), 3);

    }
}
