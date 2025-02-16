<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11501Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH11501AbstractTestEntity::class,
            GH11501TestEntityOne::class,
            GH11501TestEntityTwo::class,
            GH11501TestEntityHolder::class,
        ]);
    }

    /** @throws ORMException */
    public function testDeleteOneToManyCollectionWithSingleTableInheritance(): void
    {
        $testEntityOne    = new GH11501TestEntityOne();
        $testEntityTwo    = new GH11501TestEntityTwo();
        $testEntityHolder = new GH11501TestEntityHolder();

        $testEntityOne->testEntityHolder = $testEntityHolder;
        $testEntityHolder->testEntities->add($testEntityOne);

        $testEntityTwo->testEntityHolder = $testEntityHolder;
        $testEntityHolder->testEntities->add($testEntityTwo);

        $em = $this->getEntityManager();
        $em->persist($testEntityOne);
        $em->persist($testEntityTwo);
        $em->persist($testEntityHolder);
        $em->flush();

        $testEntityHolder->testEntities = new ArrayCollection();
        $em->persist($testEntityHolder);
        $em->flush();
        $em->refresh($testEntityHolder);

        static::assertEmpty($testEntityHolder->testEntities->toArray(), 'All records should have been deleted');
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'one_to_many_single_table_inheritance_test_entities_parent_join')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    'test_entity_one' => 'GH11501TestEntityOne',
    'test_entity_two' => 'GH11501TestEntityTwo',
])]
class GH11501AbstractTestEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\ManyToOne(targetEntity: 'GH11501TestEntityHolder', inversedBy: 'testEntities')]
    #[ORM\JoinColumn(name: 'test_entity_holder_id', referencedColumnName: 'id')]
    public GH11501TestEntityHolder $testEntityHolder;
}


#[ORM\Entity]
class GH11501TestEntityOne extends GH11501AbstractTestEntity
{
}

#[ORM\Entity]
class GH11501TestEntityTwo extends GH11501AbstractTestEntity
{
}

#[ORM\Entity]
class GH11501TestEntityHolder
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\OneToMany(
        targetEntity: 'GH11501AbstractTestEntity',
        mappedBy: 'testEntityHolder',
        orphanRemoval: true,
    )]
    public Collection $testEntities;

    public function __construct()
    {
        $this->testEntities = new ArrayCollection();
    }
}
