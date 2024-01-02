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

#[ORM\Entity]
class GH8415ToManyAssociationTarget
{
    /** @var int */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public $id;

    /** @var GH8415ToManyBaseClass */
    #[ORM\ManyToOne(targetEntity: GH8415ToManyBaseClass::class, inversedBy: 'targets')]
    public $base;
}

#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'discriminator', type: 'string')]
#[ORM\DiscriminatorMap(['1' => GH8415ToManyBaseClass::class, '2' => GH8415ToManyLeafClass::class])]
class GH8415ToManyBaseClass
{
    /** @var int */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    public $id;

    /** @var Collection */
    #[ORM\OneToMany(targetEntity: GH8415ToManyAssociationTarget::class, mappedBy: 'base')]
    public $targets;
}

#[ORM\MappedSuperclass]
class GH8415ToManyMappedSuperclass extends GH8415ToManyBaseClass
{
}

#[ORM\Entity]
class GH8415ToManyLeafClass extends GH8415ToManyMappedSuperclass
{
    /** @var string */
    #[ORM\Column(type: 'string')]
    public $leafField;
}
