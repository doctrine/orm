<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\DDC11871;

use Doctrine\Tests\OrmFunctionalTestCase;

use const PHP_VERSION_ID;

class DDC11871Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (PHP_VERSION_ID < 80400) {
            self::markTestSkipped('The ' . self::class . ' requires PHP 8.4.');
        }

        $this->setUpEntitySchema([
            DDC11871User::class,
            DDC11871Order::class,
        ]);
    }

    public function testEntityHydratation(): void
    {
        $user = new DDC11871User('Some company');
        $this->_em->persist($user);
        $order = new DDC11871Order($user);
        $this->_em->persist($order);
        $this->_em->flush();
        $this->_em->clear();

        $hydrated = $this->_em->getRepository(DDC11871Order::class)->findAll();
        self::assertCount(1, $hydrated);
    }
}
