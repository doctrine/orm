<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\Persistence\Proxy;
use Doctrine\Tests\OrmFunctionalTestCase;

use function getenv;

class GH10466Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10466Item::class,
            GH10466SubItem::class
        );
    }

    public function testIssue(): void
    {
        $item1         = new GH10466Item();
        $item2         = new GH10466Item();
        $item2->parent = $item1;
        $this->_em->persist($item1);
        $this->_em->persist($item2);
        $this->_em->flush();
        $this->_em->clear();

        $item3 = $this->_em->find(GH10466Item::class, $item2->id);
        self::assertInstanceOf(GH10466Item::class, $item3->parent);

        if (! getenv('ENABLE_SECOND_LEVEL_CACHE')) {
            self::assertInstanceOf(Proxy::class, $item3->parent);
        }
    }
}

/**
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="integer")
 * @DiscriminatorMap({"0" = "GH10466Item", "1" = "GH10466SubItem"})
 */
class GH10466Item
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var GH10466Item
     * @ManyToOne(targetEntity="GH10466Item", inversedBy="children")
     * @JoinColumn(name="parentId", referencedColumnName="id")
     */
    public $parent;

    public function __construct()
    {
    }
}

/** @Entity */
class GH10466SubItem extends GH10466Item
{
}
