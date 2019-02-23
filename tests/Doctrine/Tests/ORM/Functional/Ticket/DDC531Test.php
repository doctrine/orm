<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\Proxy\GhostObjectInterface;

class DDC531Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC531Item::class),
                $this->em->getClassMetadata(DDC531SubItem::class),
            ]
        );
    }

    public function testIssue() : void
    {
        $item1         = new DDC531Item();
        $item2         = new DDC531Item();
        $item2->parent = $item1;
        $item1->getChildren()->add($item2);
        $this->em->persist($item1);
        $this->em->persist($item2);
        $this->em->flush();
        $this->em->clear();

        $item3 = $this->em->find(DDC531Item::class, $item2->id); // Load child item first (id 2)
        // parent will already be loaded, cannot be lazy because it has mapped subclasses and we would not
        // know which proxy type to put in.
        self::assertInstanceOf(DDC531Item::class, $item3->parent);
        self::assertNotInstanceOf(GhostObjectInterface::class, $item3->parent);
        $item4 = $this->em->find(DDC531Item::class, $item1->id); // Load parent item (id 1)
        self::assertNull($item4->parent);
        self::assertNotNull($item4->getChildren());
        self::assertTrue($item4->getChildren()->contains($item3)); // lazy-loads children
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="integer")
 * @ORM\DiscriminatorMap({"0" = DDC531Item::class, "1" = DDC531SubItem::class})
 */
class DDC531Item
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /** @ORM\OneToMany(targetEntity=DDC531Item::class, mappedBy="parent") */
    protected $children;

    /**
     * @ORM\ManyToOne(targetEntity=DDC531Item::class, inversedBy="children")
     * @ORM\JoinColumn(name="parentId", referencedColumnName="id")
     */
    public $parent;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildren()
    {
        return $this->children;
    }
}

/**
 * @ORM\Entity
 */
class DDC531SubItem extends DDC531Item
{
}
