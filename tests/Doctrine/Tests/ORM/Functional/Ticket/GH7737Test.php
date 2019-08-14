<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH7737
 */
class GH7737Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH7737Group::class, GH7737Person::class]);

        $group1 = new GH7737Group(1, 'Test 1');
        $person = new GH7737Person(1);
        $person->groups->add($group1);

        $this->_em->persist($person);
        $this->_em->persist($group1);
        $this->_em->persist(new GH7737Group(2, 'Test 2'));
        $this->_em->flush();
        $this->_em->clear();
    }

    /**
     * @test
     */
    public function memberOfCriteriaShouldBeCompatibleWithQueryBuilder() : void
    {
        $query = $this->_em->createQueryBuilder()
            ->select('person')
            ->from(GH7737Person::class, 'person')
            ->addCriteria(Criteria::create()->where(Criteria::expr()->memberOf(':group', 'person.groups')))
            ->getQuery();

        $group1   = $this->_em->find(GH7737Group::class, 1);
        $matching = $query->setParameter('group', $group1)->getOneOrNullResult();

        self::assertInstanceOf(GH7737Person::class, $matching);
        self::assertSame(1, $matching->id);

        $group2      = $this->_em->find(GH7737Group::class, 2);
        $notMatching = $query->setParameter('group', $group2)->getOneOrNullResult();

        self::assertNull($notMatching);
    }
}

/**
 * @Entity
 */
class GH7737Group
{
    /**
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /** @Column */
    public $name;

    public function __construct(int $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}

/**
 * @Entity
 */
class GH7737Person
{
    /**
     * @Id
     * @Column(type="integer")
     */
    public $id;

    /**
     * @ManyToMany(targetEntity=GH7737Group::class)
     * @JoinTable(inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id", unique=true)})
     */
    public $groups;

    public function __construct(int $id)
    {
        $this->id     = $id;
        $this->groups = new ArrayCollection();
    }
}
