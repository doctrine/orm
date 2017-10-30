<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH6029Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH6029User::class,
                GH6029Group::class,
                GH6029Group2::class,
                GH6029Product::class,
                GH6029Feature::class,
            ]
        );
    }

    /**
     * Verifies that when wrong entity is persisted via relationship field, the error message does not correctly state
     * the expected class name.
     *
     * @group 6029
     */
    public function testManyToManyAssociation() : void
    {
        $user = new GH6029User();
        $user->groups->add(new GH6029Group2());

        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Expected value of type "%s" for association field "%s#$groups", got "%s" instead.',
                GH6029Group::class,
                GH6029User::class,
                GH6029Group2::class
            )
        );

        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Verifies that when wrong entity is persisted via relationship field, the error message does not correctly state
     * the expected class name.
     *
     * @group 6029
     */
    public function testOneToManyAssociation() : void
    {
        $product = new GH6029Product();
        $product->features->add(new GH6029Group2());

        $this->expectException(ORMInvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Expected value of type "%s" for association field "%s#$features", got "%s" instead.',
                GH6029Feature::class,
                GH6029Product::class,
                GH6029Group2::class
            )
        );

        $this->_em->persist($product);
        $this->_em->flush();
    }
}

/** @Entity */
class GH6029User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToMany(targetEntity=GH6029Group::class, cascade={"all"}) */
    public $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
}

/** @Entity */
class GH6029Group
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/** @Entity */
class GH6029Group2
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

/** @Entity */
class GH6029Product
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @OneToMany(targetEntity=GH6029Feature::class, mappedBy="product", cascade={"all"})
     */
    public $features;

    public function __construct()
    {
        $this->features = new ArrayCollection();
    }
}

/** @Entity */
class GH6029Feature
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @ManyToOne(targetEntity=GH6029Product::class, inversedBy="features")
     * @JoinColumn(name="product_id", referencedColumnName="id")
     */
    public $product;
}
