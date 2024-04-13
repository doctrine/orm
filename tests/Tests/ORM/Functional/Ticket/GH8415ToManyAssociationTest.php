<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmTestCase;

class GH8415ToManyAssociationTest extends OrmTestCase
{
    public function testToManyAssociationOnBaseClassAllowedWhenThereAreMappedSuperclassesAsChildren(): void
    {
        $this->expectNotToPerformAssertions();

        $em = $this->getTestEntityManager();
        $em->getClassMetadata(GH8415ToManyLeafClass::class);
    }
}

/**
 * @ORM\Entity
 */
class GH8415ToManyAssociationTarget
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
     * @ORM\ManyToOne(targetEntity="GH8415ToManyBaseClass", inversedBy="targets")
     *
     * @var GH8415ToManyBaseClass
     */
    public $base;
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator", type="string")
 * @ORM\DiscriminatorMap({"1" = "Doctrine\Tests\ORM\Functional\Ticket\GH8415ToManyBaseClass", "2" = "Doctrine\Tests\ORM\Functional\Ticket\GH8415ToManyLeafClass"})
 */
class GH8415ToManyBaseClass
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
     * @ORM\OneToMany(targetEntity="GH8415ToManyAssociationTarget", mappedBy="base")
     *
     * @var Collection
     */
    public $targets;
}

/**
 * @ORM\MappedSuperclass
 */
class GH8415ToManyMappedSuperclass extends GH8415ToManyBaseClass
{
}

/**
 * @ORM\Entity
 */
class GH8415ToManyLeafClass extends GH8415ToManyMappedSuperclass
{
    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    public $leafField;
}
