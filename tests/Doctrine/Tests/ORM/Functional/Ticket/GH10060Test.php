<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-10060
 */
class GH10060Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10060A::class,
            GH10060B::class,
        );
    }

    public function testIssue(): void
    {
        $a = new GH10060A();
        $b = new GH10060B();
        $b->items->add($a);
        $a->items->add($b);

        $this->_em->persist($b);
        $this->_em->persist($a);
        $this->_em->flush();

        $this->_em->remove($a);
        $this->_em->flush();
        $this->_em->flush();
    }
}

/**
 * @Entity
 * @Table(name="GH10060A_items")
 */
class GH10060A
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @var GH10060B[]
     * @ManyToMany(targetEntity=GH10060B::class, mappedBy="items")
     */
    public $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }
}

/**
 * @Entity
 * @Table(name="GH10060B_items")
 */
class GH10060B
{
    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @var GH10060A[]
     * @ManyToMany (targetEntity=GH10060A::class, inversedBy="items")
     */
    public $items;
}
