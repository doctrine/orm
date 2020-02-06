<?php

namespace Doctrine\Test\ORM\Functional\Ticket;

use DateTimeImmutable;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH8014Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (PHP_VERSION_ID >= 70400) {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(GH8014Foo::class),
                ]
            );
        }
    }

    public function testNonNullablePropertyOfEmbeddableCanBeHydratedWithNullValueInDatabase()
    {
        if (PHP_VERSION_ID < 70400) {
            self::markTestSkipped('This test only applies to PHP versions higher than 7.4');

            return;
        }

        $foo = $this->createEntityWithoutTheNullablePropertySet();
        $foo = $this->_em->find(
            GH8014Foo::class,
            $foo->id
        ); // Used to throw "Typed property Doctrine\Test\ORM\Functional\Ticket\GH8014Bar::$startDate must be an instance of DateTimeImmutable, null used"

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Typed property Doctrine\Test\ORM\Functional\Ticket\GH8014Bar::$startDate must not be accessed before initialization');
        $foo->bar->startDate;

        $foo = $this->createEntityWithTheNullablePropertySet();
        $foo = $this->_em->find(GH8014Foo::class, $foo->id);

        $this->assertNotNull($foo->bar->startDate);
    }

    private function createEntityWithoutTheNullablePropertySet(): GH8014Foo
    {
        $foo = new GH8014Foo();
        $this->_em->persist($foo);
        $this->_em->flush();
        $this->_em->clear();

        return $foo;
    }

    private function createEntityWithTheNullablePropertySet(): GH8014Foo
    {
        $foo = new GH8014Foo();
        $foo->bar = new GH8014Bar();
        $foo->bar->startDate = new DateTimeImmutable();
        $this->_em->persist($foo);
        $this->_em->flush();
        $this->_em->clear();

        return $foo;
    }
}

if (PHP_VERSION_ID >= 70400) {
    require_once __DIR__.'/GH8014_php74.php';
}
