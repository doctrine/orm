<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11500Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH11500AbstractTestEntity::class,
            GH11500TestEntityOne::class,
            GH11500TestEntityTwo::class,
            GH11500TestEntityHolder::class,
        ]);
    }

    /** @throws ORMException */
    public function testDeleteOneToManyCollectionWithSingleTableInheritance(): void
    {
        $testEntityOne    = new GH11500TestEntityOne();
        $testEntityTwo    = new GH11500TestEntityTwo();
        $testEntityHolder = new GH11500TestEntityHolder();

        $testEntityOne->testEntityHolder = $testEntityHolder;
        $testEntityHolder->testEntityOnes->add($testEntityOne);

        $testEntityTwo->testEntityHolder = $testEntityHolder;
        $testEntityHolder->testEntityTwos->add($testEntityTwo);

        $em = $this->getEntityManager();
        $em->persist($testEntityOne);
        $em->persist($testEntityTwo);
        $em->persist($testEntityHolder);
        $em->flush();

        $testEntityTwosBeforeRemovalOfTestEntityOnes = $testEntityHolder->testEntityTwos->toArray();

        $testEntityHolder->testEntityOnes = new ArrayCollection();
        $em->persist($testEntityHolder);
        $em->flush();
        $em->refresh($testEntityHolder);

        static::assertEmpty($testEntityHolder->testEntityOnes->toArray(), 'All records should have been deleted');
        static::assertEquals($testEntityTwosBeforeRemovalOfTestEntityOnes, $testEntityHolder->testEntityTwos->toArray(), 'Different Entity\'s records should not have been deleted');
    }
}



#[ORM\Entity]
#[ORM\Table(name: 'one_to_many_single_table_inheritance_test_entities')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap(['test_entity_one' => 'GH11500TestEntityOne', 'test_entity_two' => 'GH11500TestEntityTwo'])]
class GH11500AbstractTestEntity
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;
}


#[ORM\Entity]
class GH11500TestEntityOne extends GH11500AbstractTestEntity
{
    #[ORM\ManyToOne(inversedBy:'testEntityOnes')]
    #[ORM\JoinColumn(name:'test_entity_holder_id', referencedColumnName:'id')]
    public GH11500TestEntityHolder|null $testEntityHolder = null;
}

#[ORM\Entity]
class GH11500TestEntityTwo extends GH11500AbstractTestEntity
{
    #[ORM\ManyToOne(inversedBy:'testEntityTwos')]
    #[ORM\JoinColumn(name:'test_entity_holder_id', referencedColumnName:'id')]
    public GH11500TestEntityHolder|null $testEntityHolder = null;
}

#[ORM\Entity]
class GH11500TestEntityHolder
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\OneToMany(targetEntity: 'GH11500TestEntityOne', mappedBy: 'testEntityHolder', orphanRemoval: true)]
    public Collection $testEntityOnes;

    #[ORM\OneToMany(targetEntity: 'GH11500TestEntityTwo', mappedBy: 'testEntityHolder', orphanRemoval: true)]
    public Collection $testEntityTwos;

    public function __construct()
    {
        $this->testEntityOnes = new ArrayCollection();
        $this->testEntityTwos = new ArrayCollection();
    }
}
