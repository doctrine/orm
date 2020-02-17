<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Tests\OrmFunctionalTestCase;
use function sprintf;

final class GH6029Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
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

        $this->em->persist($user);
        $this->em->flush();
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

        $this->em->persist($product);
        $this->em->flush();
    }
}

/** @ORM\Entity */
class GH6029User
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\ManyToMany(targetEntity=GH6029Group::class, cascade={"all"}) */
    public $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
}

/** @ORM\Entity */
class GH6029Group
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/** @ORM\Entity */
class GH6029Group2
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
}

/** @ORM\Entity */
class GH6029Product
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /** @ORM\OneToMany(targetEntity=GH6029Feature::class, mappedBy="product", cascade={"all"}) */
    public $features;

    public function __construct()
    {
        $this->features = new ArrayCollection();
    }
}

/** @ORM\Entity */
class GH6029Feature
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH6029Product::class, inversedBy="features")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    public $product;
}
