<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7162Test extends OrmFunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpEntitySchema([
            GH7162Parent::class,
            GH7162Child::class,
        ]);
    }

    /**
     * @group 7067
     */
    public function testIssueWithDetachedEntity(): void
    {
        // Create a parent without children
        $parent = new GH7162Parent();
        $this->em->persist($parent);
        $this->em->flush();
        $this->em->clear();

        $parentId = $parent->id;

        // Fetch the parent as a non-cached entity
        /** @var GH7162Parent $parent */
        $parent = $this->em->find(GH7162Parent::class, $parentId);

        // Create a new child and add it to the persistent collection
        // of children: $parent->children
        $child1 = new GH7162Child();
        $parent->addChild($child1);

        // Then, remove the same child, causing an orphan removal
        $parent->removeChild($child1);

        $caughtException = null;
        $message = '';
        try {
            $this->em->flush();
        } catch (ORMInvalidArgumentException $exception) {
            $message = $exception->getMessage();
            $caughtException = $exception;
        }
        $this->em->clear();

        self::assertNull(
            $caughtException,
            'Child entity is detached and should not be scheduled for orphan removal, but it is.'
            . ' '
            . 'Message: '
            . $message
        );
    }
}

/**
 * @ORM\Entity
 */
class GH7162Parent
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity=GH7162Child::class, mappedBy="parent", cascade={"remove","persist"}, orphanRemoval=true)
     *
     * @var GH7162Child[]|Collection
     */
    public $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /**
     * @param GH7162Child $child
     */
    public function addChild(GH7162Child $child): void
    {
        if ($this->children->contains($child)) {
            return;
        }

        $this->children->add($child);
    }

    /**
     * @param GH7162Child $child
     */
    public function removeChild(GH7162Child $child): void
    {
        if (!$this->children->contains($child)) {
            return;
        }

        $this->children->removeElement($child);
    }
}

/**
 * @ORM\Entity
 */
class GH7162Child
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH7162Parent::class, inversedBy="children")
     * @ORM\JoinColumn(referencedColumnName="parent_id")
     *
     * @var GH7162Parent
     */
    public $parent;
}
