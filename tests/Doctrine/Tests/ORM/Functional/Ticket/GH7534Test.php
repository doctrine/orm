<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Tests\OrmFunctionalTestCase;
use function assert;

final class GH7534Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH7534Person::class,
                GH7534PhoneNumber::class,
            ]
        );

        $this->_em->persist(
            new GH7534Person(
                [
                    new GH7534PhoneNumber('123', 0),
                    new GH7534PhoneNumber('456', 1),
                    new GH7534PhoneNumber('789', 2),
                ]
            )
        );

        $this->_em->flush();
        $this->_em->clear();
    }

    /**
     * @test
     * @group 7534
     */
    public function collectionShouldBe() : void
    {
        $person = $this->_em->find(GH7534Person::class, 1);
        assert($person instanceof GH7534Person);

        $criteria = Criteria::create()->where(Criteria::expr()->eq('type', 1))
                                      ->orWhere(Criteria::expr()->eq('type', 2));

        $phoneNumbers = $person->phoneNumbers->matching($criteria);

        self::assertCount(2, $phoneNumbers);
        self::assertSame('456', $phoneNumbers->get(0)->number);
        self::assertSame('789', $phoneNumbers->get(1)->number);
    }
}

/**
 * @Entity
 */
class GH7534Person
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @ManyToMany(targetEntity=GH7534PhoneNumber::class, cascade={"all"})
     * @var Collection
     */
    public $phoneNumbers;

    /**
     * @param GH7534PhoneNumber[] $phoneNumbers
     */
    public function __construct(array $phoneNumbers = [])
    {
        $this->phoneNumbers = new ArrayCollection($phoneNumbers);
    }
}

/**
 * @Entity
 */
class GH7534PhoneNumber
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    public $id;

    /**
     * @Column(type="string")
     * @var string
     */
    public $number;

    /**
     * @Column(type="integer")
     * @var int
     */
    public $type;

    public function __construct(string $number, int $type)
    {
        $this->number = $number;
        $this->type   = $type;
    }
}
