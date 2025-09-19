<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH12174Test extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expectNoDeprecationWithIdentifier('https://github.com/doctrine/orm/pull/10431'); // make test fail on 2.x; in 3.x, it would even throw

        $resolveTargetEntity = new ResolveTargetEntityListener();

        $resolveTargetEntity->addResolveTargetEntity(GH12174Smurf::class, GH12174BlueSmurf::class, []);

        $this->_em->getEventManager()->addEventSubscriber($resolveTargetEntity);

        $this->createSchemaForModels(
            GH12174Smurf::class,
            GH12174BlueSmurf::class,
            GH12174PapaSmurf::class
        );
    }

    public function testIt(): void
    {
        $smurf = $this->_em->getClassMetadata(GH12174Smurf::class);
        self::assertTrue($smurf->isMappedSuperclass);
        self::assertSame(GH12174Smurf::class, $smurf->getName());
        self::assertSame(GH12174BlueSmurf::class, $smurf->getAssociationMapping('children')['targetEntity']);

        $blue = $this->_em->getClassMetadata(GH12174BlueSmurf::class);
        self::assertFalse($blue->isMappedSuperclass);
        self::assertSame(GH12174BlueSmurf::class, $blue->getName());

        $papa = $this->_em->getClassMetadata(GH12174PapaSmurf::class);
        self::assertFalse($papa->isMappedSuperclass);
        self::assertSame(GH12174PapaSmurf::class, $papa->getName());
    }
}

/** @ORM\MappedSuperclass() */
#[ORM\MappedSuperclass]
class GH12174Smurf
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    public $id;

    /**
     * @ORM\ManyToOne(inversedBy="children", targetEntity="GH12174Smurf")
     * @var GH12174Smurf
     */
    #[ManyToOne(inversedBy: 'children')]
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="GH12174Smurf", mappedBy="parent")
     * @var Collection
     */
    #[OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private $children;
}

/** @ORM\Entity */
#[ORM\Entity]
class GH12174BlueSmurf extends GH12174Smurf
{
}

/** @ORM\Entity */
#[ORM\Entity]
class GH12174PapaSmurf extends GH12174Smurf
{
}
