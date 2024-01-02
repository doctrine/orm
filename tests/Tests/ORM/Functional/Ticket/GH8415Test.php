<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH8415Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH8415BaseClass::class,
                GH8415MiddleMappedSuperclass::class,
                GH8415LeafClass::class,
                GH8415AssociationTarget::class,
            ]
        );
    }

    public function testAssociationIsBasedOnBaseClass(): void
    {
        $target            = new GH8415AssociationTarget();
        $leaf              = new GH8415LeafClass();
        $leaf->baseField   = 'base';
        $leaf->middleField = 'middle';
        $leaf->leafField   = 'leaf';
        $leaf->target      = $target;

        $this->_em->persist($target);
        $this->_em->persist($leaf);
        $this->_em->flush();
        $this->_em->clear();

        $query  = $this->_em->createQuery('SELECT leaf FROM Doctrine\Tests\ORM\Functional\Ticket\GH8415LeafClass leaf JOIN leaf.target t');
        $result = $query->getOneOrNullResult();

        $this->assertInstanceOf(GH8415LeafClass::class, $result);
        $this->assertSame('base', $result->baseField);
        $this->assertSame('middle', $result->middleField);
        $this->assertSame('leaf', $result->leafField);
    }
}

/**
 * @ORM\Entity
 */
class GH8415AssociationTarget
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({"1" = "Doctrine\Tests\ORM\Functional\Ticket\GH8415BaseClass", "2" = "Doctrine\Tests\ORM\Functional\Ticket\GH8415LeafClass"})
 */
class GH8415BaseClass
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="GH8415AssociationTarget")
     *
     * @var GH8415AssociationTarget
     */
    public $target;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $baseField;
}

/**
 * @ORM\MappedSuperclass
 */
class GH8415MiddleMappedSuperclass extends GH8415BaseClass
{
    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $middleField;
}

/**
 * @ORM\Entity
 */
class GH8415LeafClass extends GH8415MiddleMappedSuperclass
{
    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $leafField;
}
