<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC599Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(DDC599Item::class),
                $this->_em->getClassMetadata(DDC599Subitem::class),
                $this->_em->getClassMetadata(DDC599Child::class),
                ]
            );
        } catch (\Exception $ignored) {}
    }

    public function testCascadeRemoveOnInheritanceHierarchy()
    {
        $item = new DDC599Subitem;
        $item->elem = "foo";
        $child = new DDC599Child;
        $child->parent = $item;
        $item->getChildren()->add($child);
        $this->_em->persist($item);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $item = $this->_em->find(DDC599Item::class, $item->id);

        $this->_em->remove($item);
        $this->_em->flush(); // Should not fail

        $this->assertFalse($this->_em->contains($item));
        $children = $item->getChildren();
        $this->assertFalse($this->_em->contains($children[0]));

        $this->_em->clear();


        $item2 = new DDC599Subitem;
        $item2->elem = "bar";
        $this->_em->persist($item2);
        $this->_em->flush();

        $child2 = new DDC599Child;
        $child2->parent = $item2;
        $item2->getChildren()->add($child2);
        $this->_em->persist($child2);
        $this->_em->flush();

        $this->_em->remove($item2);
        $this->_em->flush(); // should not fail

        $this->assertFalse($this->_em->contains($item));
        $children = $item->getChildren();
        $this->assertFalse($this->_em->contains($children[0]));
    }

    public function testCascadeRemoveOnChildren()
    {
        $class = $this->_em->getClassMetadata(DDC599Subitem::class);

        $this->assertArrayHasKey('children', $class->associationMappings);
        $this->assertTrue($class->associationMappings['children']['isCascadeRemove']);
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="integer")
 * @DiscriminatorMap({"0" = "DDC599Item", "1" = "DDC599Subitem"})
 */
class DDC599Item
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @OneToMany(targetEntity="DDC599Child", mappedBy="parent", cascade={"remove"})
     */
    protected $children;

    public function __construct()
    {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function getChildren()
    {
        return $this->children;
    }
}

/**
 * @Entity
 */
class DDC599Subitem extends DDC599Item
{
    /**
     * @Column(type="string")
     */
    public $elem;
}

/**
 * @Entity
 */
class DDC599Child
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="DDC599Item", inversedBy="children")
     * @JoinColumn(name="parentId", referencedColumnName="id")
     */
    public $parent;
}
