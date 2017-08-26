<?php

declare(strict_types=1);

namespace LazyMapTest;

use Doctrine\ORM\Internal\Hydration\Cache\LazyPropertyMap;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @link https://github.com/Ocramius/LazyMap/blob/1.0.0/tests/LazyMapTest/CallbackLazyMapTest.php
 *
 * @covers \Doctrine\ORM\Internal\Hydration\Cache\LazyPropertyMap
 */
class LazyPropertyMapTest extends TestCase
{
    /**
     * @var \LazyMap\CallbackLazyMap
     */
    protected $lazyMap;

    /**
     * @var callable|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $callback;

    protected function setUp() : void
    {
        parent::setUp();

        $this->callback = $this->getMockBuilder(stdClass::class)->setMethods(['__invoke'])->getMock();
        $this->lazyMap  = new LazyPropertyMap($this->callback);
    }

    public function testDirectPropertyAccess()
    {
        $count = 0;
        $this
            ->callback
            ->expects(self::exactly(3))
            ->method('__invoke')
            ->will(self::returnCallback(function ($name) use (& $count) {
                $count += 1;

                return $name . ' - ' . $count;
            }));

        $this->assertSame('foo - 1', $this->lazyMap->foo);
        $this->assertSame('bar - 2', $this->lazyMap->bar);
        $this->assertSame('baz\\tab - 3', $this->lazyMap->{'baz\\tab'});
    }
}
