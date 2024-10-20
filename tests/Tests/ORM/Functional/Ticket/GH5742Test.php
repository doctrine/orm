<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH5742Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH5742Person::class,
            GH5742Toothbrush::class,
            GH5742ToothpasteBrand::class,
        );
    }

    public function testUpdateOneToOneToNewEntityBeforePreviousEntityCanBeRemoved(): void
    {
        $person             = new GH5742Person();
        $oldToothbrush      = new GH5742Toothbrush();
        $person->toothbrush = $oldToothbrush;

        $this->_em->persist($person);
        $this->_em->persist($oldToothbrush);
        $this->_em->flush();

        $oldToothbrushId = $oldToothbrush->id;

        $newToothbrush      = new GH5742Toothbrush();
        $person->toothbrush = $newToothbrush;

        $this->_em->remove($oldToothbrush);
        $this->_em->persist($newToothbrush);

        // The flush operation will have to make sure the new toothbrush
        // has been written to the database
        // _before_ the person can be updated to refer to it.
        // Likewise, the update must have happened _before_ the old
        // toothbrush can be removed (non-nullable FK constraint).

        $this->_em->flush();

        $this->_em->clear();
        self::assertSame($newToothbrush->id, $this->_em->find(GH5742Person::class, $person->id)->toothbrush->id);
        self::assertNull($this->_em->find(GH5742Toothbrush::class, $oldToothbrushId));
    }

    public function testManyToManyCollectionUpdateBeforeRemoval(): void
    {
        $person             = new GH5742Person();
        $person->toothbrush = new GH5742Toothbrush(); // to satisfy not-null constraint
        $this->_em->persist($person);

        $oldMice = new GH5742ToothpasteBrand();
        $this->_em->persist($oldMice);

        $person->preferredBrands->set(1, $oldMice);
        $this->_em->flush();

        $oldBrandId = $oldMice->id;

        $newSpice = new GH5742ToothpasteBrand();
        $this->_em->persist($newSpice);

        $person->preferredBrands->set(1, $newSpice);

        $this->_em->remove($oldMice);

        // The flush operation will have to make sure the new brand
        // has been written to the database _before_ it can be referred
        // to from the m2m join table.
        // Likewise, the old join table entry must have been removed
        // _before_ the old brand can be removed.

        $this->_em->flush();

        $this->_em->clear();
        self::assertCount(1, $this->_em->find(GH5742Person::class, $person->id)->preferredBrands);
        self::assertNull($this->_em->find(GH5742ToothpasteBrand::class, $oldBrandId));
    }
}

#[ORM\Entity]
class GH5742Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\OneToOne(targetEntity: 'GH5742Toothbrush', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    public GH5742Toothbrush $toothbrush;

    /** @var Collection<GH5742ToothpasteBrand> */
    #[ORM\ManyToMany(targetEntity: 'GH5742ToothpasteBrand')]
    #[ORM\JoinTable('gh5742person_gh5742toothpastebrand')]
    #[ORM\JoinColumn(name: 'person_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'brand_id', referencedColumnName: 'id')]
    public Collection $preferredBrands;

    public function __construct()
    {
        $this->preferredBrands = new ArrayCollection();
    }
}

#[ORM\Entity]
class GH5742Toothbrush
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public int $id;
}

#[ORM\Entity]
class GH5742ToothpasteBrand
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    public int $id;
}
