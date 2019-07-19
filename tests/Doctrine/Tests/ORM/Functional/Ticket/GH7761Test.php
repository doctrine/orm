<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7761Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH7761Entity::class,
            GH7761ChildEntity::class,
        ]);

        $parent = new GH7761Entity();
        $child  = new GH7761ChildEntity();
        $parent->children->add($child);

        $this->em->persist($parent);
        $this->em->persist($child);
        $this->em->flush();
        $this->em->clear();
    }

    public function testCollectionClearDoesNotClearIfNotPersisted() : void
    {
        /** @var GH7761Entity $entity */
        $entity = $this->em->find(GH7761Entity::class, 1);
        $entity->children->clear();
        $this->em->persist(new GH7761Entity());
        $this->em->flush();

        $this->em->clear();

        $entity = $this->em->find(GH7761Entity::class, 1);
        self::assertCount(1, $entity->children);

        $this->em->clear();
    }
}

/**
 * @ORM\Entity
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class GH7761Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\GH7761ChildEntity", cascade={"all"})
     * @ORM\JoinTable(name="gh7761_to_child",
     *     joinColumns={@ORM\JoinColumn(name="entity_id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="child_id")}
     * )
     */
    public $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }
}

/**
 * @ORM\Entity
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class GH7761ChildEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
}
