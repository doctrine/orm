<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Here, we have a cyclic dependency between entities, but all associations are nullable.
 * But, we don't have "SET NULL" cascade operations defined at the database level.
 *
 * The ORM is able to perform the INSERT operation, since it can NULL out associations
 * and schedule deferred updates to break the cycles.
 *
 * However, it is unable to DELETE the entities. For pending deletions, associations are _not_
 * first UPDATEd to be NULL in the database before the delete happens.
 *
 * By adding ON CASCADE SET NULL (pushing the problem to the DB level explicitly) it will work.
 */
final class GH5665CommitOrderTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH5665CommitOrderItem::class, GH5665CommitOrderSubitem::class]);
    }

    public function testIssue(): void
    {
        $item = new GH5665CommitOrderItem();
        $sub1 = new GH5665CommitOrderSubitem();
        $sub2 = new GH5665CommitOrderSubitem();
        $item->addItem($sub1);
        $item->addItem($sub2);
        $item->featuredItem = $sub2;
        $this->_em->persist($item);
        $this->_em->flush();

        $this->expectNotToPerformAssertions();

        $this->_em->remove($item);
        $this->_em->flush();
    }
}

/**
 * @ORM\Entity
 */
class GH5665CommitOrderItem
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="GH5665CommitOrderSubitem", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     *
     * @var Collection
     */
    public $items;

    /**
     * @ORM\ManyToOne(targetEntity="GH5665CommitOrderSubitem")
     *
     * Adding the following would make the test pass, since it shifts responsibility for
     * NULLing references to the DB layer. The #5665 issue is about the request that this
     * happen on the ORM level.
     * > @-ORM\JoinColumn(onDelete="SET NULL")
     *
     * @var GH5665CommitOrderSubitem
     */
    public $featuredItem;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function addItem(GH5665CommitOrderSubitem $item)
    {
        $this->items[] = $item;
        $item->item = $this;
    }
}

/**
 * @ORM\Entity
 */
class GH5665CommitOrderSubitem
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var integer
     */
    public $id;

    /**
     * @var GH5665CommitOrderItem
     *
     * @ORM\ManyToOne(targetEntity="GH5665CommitOrderItem", inversedBy="items")
     */
    public $item;
}
