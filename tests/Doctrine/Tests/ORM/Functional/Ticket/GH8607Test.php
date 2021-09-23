<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;

class GH8607Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema([
                $this->_em->getClassMetadata(GH8607ParentEntity::class),
                $this->_em->getClassMetadata(GH8607ChildEntity::class),
            ]);
        } catch (Exception $e) {
            // skip errors
        }
    }

    public function testInvalidCollectionReturn(): void
    {
        $parent1 = new GH8607ParentEntity();
        $parent2 = new GH8607ParentEntity();

        $child = new GH8607ChildEntity();
        $parent2->getChildren()->add($child);

        $this->_em->persist($parent1);
        $this->_em->persist($parent2);
        $this->_em->persist($child);

        $this->_em->flush();

        $parentId = $parent1->getId();
        $childId  = $child->getId();

        $this->_em->clear();

        $parent = $this->_em->find(GH8607ParentEntity::class, $parentId);

        $this->assertNotNull($parent);

        $return = $parent->getChildren()->get($childId);

        $this->assertNull($return);
    }
}

/**
 * @Entity
 */
class GH8607ParentEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @OneToMany(targetEntity="GH8607ChildEntity", mappedBy="parent", indexBy="id", fetch="EXTRA_LAZY")
     * @var ArrayCollection
     */
    protected $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }
}

/**
 * @Entity
 */
class GH8607ChildEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="GH8607ParentEntity", inversedBy="childs")
     * @var GH8607ParentEntity
     */
    protected $parent;

    public function getId(): int
    {
        return $this->id;
    }

    public function getParent(): GH8607ParentEntity
    {
        return $this->parent;
    }
}
