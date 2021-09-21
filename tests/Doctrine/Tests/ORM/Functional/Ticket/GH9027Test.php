<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

class GH9027Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(GH9027Customer::class),
                    $this->_em->getClassMetadata(GH9027Cart::class),
                ]
            );
        } catch (Exception $e) {
        }
    }

    /**
     * @group GH-9027
     */
    public function testUnitOfWorkHandlesNullRelations(): void
    {
        $uow = new UnitOfWork($this->_em);
        $hints = ['fetchMode' => [GH9027Cart::class => ['customer' => ClassMetadata::FETCH_EAGER]]];
        $cart = $uow->createEntity(GH9027Cart::class, ['id' => 1, 'customer' => 24252], $hints);

        $this->assertEquals(null, $cart->customer);
    }
}

/** @Entity */
class GH9027Customer
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @var GH9027Cart
     * @OneToOne(targetEntity="GH9027Cart", mappedBy="customer")
     */
    public $cart;
}

/** @Entity */
class GH9027Cart
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @var GH9027Customer
     * @OneToOne(targetEntity="GH9027Customer", inversedBy="cart")
     * @JoinColumn(name="customer", referencedColumnName="id")
     */
    public $customer;
}
