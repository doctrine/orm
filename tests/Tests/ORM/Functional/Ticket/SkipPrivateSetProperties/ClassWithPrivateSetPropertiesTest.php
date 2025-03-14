<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\SkipPrivateSetProperties;

use Doctrine\Tests\OrmFunctionalTestCase;

use const PHP_VERSION_ID;

class ClassWithPrivateSetPropertiesTest extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        if (PHP_VERSION_ID < 80400) {
            self::markTestSkipped('The ' . self::class . ' requires PHP 8.4.');
        }

        $this->setUpEntitySchema([
            UserPrivateSetProperties::class,
            OrderPrivateSetProperties::class,
        ]);
    }

    public function testEntityHydratation(): void
    {
        $user = new UserPrivateSetProperties('Some company');
        $this->_em->persist($user);
        $order = new OrderPrivateSetProperties($user);
        $this->_em->persist($order);
        $this->_em->flush();
        $this->_em->clear();

        $hydrated = $this->_em->getRepository(OrderPrivateSetProperties::class)->findAll();
        self::assertCount(1, $hydrated);
    }
}
