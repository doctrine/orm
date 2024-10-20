<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11386;

use Doctrine\Tests\OrmFunctionalTestCase;

final class GH11386Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH11386EntityCart::class,
            GH11386EntityCustomer::class,
        );
    }

    public function testInitializeClonedProxy(): void
    {
        $cart = new GH11386EntityCart();
        $cart->setAmount(1000);

        $customer = new GH11386EntityCustomer();
        $customer->setName('John Doe')
            ->setType(GH11386EnumType::MALE)
            ->setCart($cart);

        $this->_em->persist($cart);
        $this->_em->flush();
        $this->_em->clear();

        $cart     = $this->_em->find(GH11386EntityCart::class, 1);
        $customer = clone $cart->getCustomer();
        self::assertEquals('John Doe', $customer->getName());
    }
}
