<?php

namespace Doctrine\Tests\Common\Collections;

use Doctrine\Tests;

require_once __DIR__ . '/../../TestInit.php';

class CollectionTest extends \Doctrine\Tests\DoctrineTestCase
{
    private $_coll;

    protected function setUp()
    {
        $this->_coll = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function testIssetAndUnset()
    {
        $this->assertFalse(isset($this->_coll[0]));
        $this->_coll->add('testing');
        $this->assertTrue(isset($this->_coll[0]));
        unset($this->_coll[0]);
        $this->assertFalse(isset($this->_coll[0]));
    }

    public function testToString()
    {
        $this->_coll->add('testing');
        $this->assertTrue(is_string((string) $this->_coll));
    }

    public function testRemovingNonExistentEntryReturnsNull()
    {
        $this->assertEquals(null, $this->_coll->remove('testing_does_not_exist'));
    }

    public function testExists()
    {
        $this->_coll->add("one");
        $this->_coll->add("two");
        $exists = $this->_coll->exists(function($k, $e) { return $e == "one"; });
        $this->assertTrue($exists);
        $exists = $this->_coll->exists(function($k, $e) { return $e == "other"; });
        $this->assertFalse($exists);
    }

    public function testMap()
    {
        $this->_coll->add(1);
        $this->_coll->add(2);
        $res = $this->_coll->map(function($e) { return $e * 2; });
        $this->assertEquals(array(2, 4), $res->toArray());
    }

    public function testFilter()
    {
        $this->_coll->add(1);
        $this->_coll->add("foo");
        $this->_coll->add(3);
        $res = $this->_coll->filter(function($e) { return is_numeric($e); });
        $this->assertEquals(array(0 => 1, 2 => 3), $res->toArray());
    }

    public function testFirstAndLast()
    {
        $this->_coll->add('one');
        $this->_coll->add('two');

        $this->assertEquals($this->_coll->first(), 'one');
        $this->assertEquals($this->_coll->last(), 'two');
    }

    public function testArrayAccess()
    {
        $this->_coll[] = 'one';
        $this->_coll[] = 'two';

        $this->assertEquals($this->_coll[0], 'one');
        $this->assertEquals($this->_coll[1], 'two');

        unset($this->_coll[0]);
        $this->assertEquals($this->_coll->count(), 1);
    }

    public function testContainsKey()
    {
        $this->_coll[5] = 'five';
        $this->assertTrue($this->_coll->containsKey(5));
    }

    public function testContains()
    {
        $this->_coll[0] = 'test';
        $this->assertTrue($this->_coll->contains('test'));
    }

    public function testSearch()
    {
        $this->_coll[0] = 'test';
        $this->assertEquals(0, $this->_coll->indexOf('test'));
    }

    public function testGet()
    {
        $this->_coll[0] = 'test';
        $this->assertEquals('test', $this->_coll->get(0));
    }

    public function testGetKeys()
    {
        $this->_coll[] = 'one';
        $this->_coll[] = 'two';
        $this->assertEquals(array(0, 1), $this->_coll->getKeys());
    }

    public function testGetValues()
    {
        $this->_coll[] = 'one';
        $this->_coll[] = 'two';
        $this->assertEquals(array('one', 'two'), $this->_coll->getValues());
    }

    public function testCount()
    {
        $this->_coll[] = 'one';
        $this->_coll[] = 'two';
        $this->assertEquals($this->_coll->count(), 2);
        $this->assertEquals(count($this->_coll), 2);
    }

    public function testForAll()
    {
        $this->_coll[] = 'one';
        $this->_coll[] = 'two';
        $this->assertEquals($this->_coll->forAll(function($k, $e) { return is_string($e); }), true);
        $this->assertEquals($this->_coll->forAll(function($k, $e) { return is_array($e); }), false);
    }

    public function testPartition()
    {
        $this->_coll[] = true;
        $this->_coll[] = false;
        $partition = $this->_coll->partition(function($k, $e) { return $e == true; });
        $this->assertEquals($partition[0][0], true);
        $this->assertEquals($partition[1][0], false);
    }

    public function testClear()
    {
        $this->_coll[] = 'one';
        $this->_coll[] = 'two';
        $this->_coll->clear();
        $this->assertEquals($this->_coll->isEmpty(), true);
    }

    public function testRemove()
    {
        $this->_coll[] = 'one';
        $this->_coll[] = 'two';
        $this->_coll->remove(0);
        $this->assertEquals($this->_coll->contains('one'), false);
    }

    public function testRemoveElement()
    {
        $this->_coll[] = 'one';
        $this->_coll[] = 'two';
        $this->_coll->removeElement('two');
        $this->assertEquals($this->_coll->contains('two'), false);
    }
}