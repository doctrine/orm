<?php

namespace Doctrine\Tests\Common\Collections;

use Doctrine\Tests;

require_once dirname(__FILE__) . '/../../TestInit.php';

/**
 * Collection tests.
 *
 * @author robo
 * @since 2.0
 */
class CollectionTest extends \Doctrine\Tests\DoctrineTestCase {
    private $_coll;

    protected function setUp() {
        $this->_coll = new \Doctrine\Common\Collections\Collection;
    }

    /*public function testExists() {
        $this->_coll->add("one");
        $this->_coll->add("two");
        $exists = $this->_coll->exists(function($key, $element) { return $element == "one"; });
        $this->assertTrue($exists);
        $exists = $this->_coll->exists(function($key, $element) { return $element == "other"; });
        $this->assertFalse($exists);
    }

    public function testMap() {
        $this->_coll->add(1);
        $this->_coll->add(2);
        $res = $this->_coll->map(function ($e) { return $e * 2; });
        $this->assertEquals(array(2, 4), $res->unwrap());
    }

    public function testFilter() {
        $this->_coll->add(1);
        $this->_coll->add("foo");
        $this->_coll->add(3);
        $res = $this->_coll->filter(function ($e) { return is_numeric($e); });
        $this->assertEquals(array(0 => 1, 2 => 3), $res->unwrap());
    }*/
}

