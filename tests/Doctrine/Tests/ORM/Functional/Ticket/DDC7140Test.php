<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Class DDC7140Test
 *
 * @package Doctrine\Tests\ORM\Functional\Ticket
 * @group 7140
 */
class DDC7140Test extends OrmFunctionalTestCase
{
    public function testDDC7140()
    {
        $this->createData();

        /** @var DDC7140PersonUser $personUser */
        $personUser = $this->_em->getRepository(DDC7140PersonUser::class)->find(1);

        // Uncommenting this makes both asserts pass
        //self::assertInstanceOf(DDC7140Account::class, $personUser->getPerson()->getAccount());

        $queryBuilder = $this->_em->createQueryBuilder();

        $data = $queryBuilder
            ->select('personUser')
            ->addSelect('person', 'account')
            ->from(DDC7140PersonUser::class, 'personUser')
            ->join('personUser.person', 'person')
            ->join('person.account', 'account')
            ->where($queryBuilder->expr()->in('personUser.id', [1, 2]))
            ->getQuery()
            ->getResult();

        self::assertInstanceOf(DDC7140Account::class, $personUser->getPerson()->getAccount());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(DDC7140Account::class),
            $this->_em->getClassMetadata(DDC7140Person::class),
            $this->_em->getClassMetadata(DDC7140PersonUser::class),
        ]);
    }

    private function createData()
    {
        $acc    = new DDC7140Account(1);
        $person = new DDC7140Person($acc);
        $user1  = new DDC7140PersonUser(1, $person);
        $user2  = new DDC7140PersonUser(2, $person);
        $this->_em->persist($acc);
        $this->_em->persist($person);
        $this->_em->persist($user1);
        $this->_em->persist($user2);
        $this->_em->flush();
        $this->_em->clear();
    }
}

/**
 * @Entity()
 * @Table(name="ddc7140_account")
 */
class DDC7140Account
{
    /**
     * @Id()
     * @GeneratedValue(strategy="NONE")
     * @Column(name="id", type="integer", length=11)
     */
    private $id;

    /**
     * @Column(name="data", type="string", length=255)
     */
    private $data;

    public function __construct($id)
    {
        $this->id   = $id;
        $this->data = '3';
    }

    public function getId(): int
    {
        return $this->id;
    }
}

/**
 * @Entity()
 * @Table(name="ddc7140_person")
 */
class DDC7140Person
{
    /**
     * @Id()
     * @OneToOne(targetEntity=DDC7140Account::class)
     * @JoinColumn(name="account_id", referencedColumnName="id")
     */
    private $account;

    /**
     * @Column(name="data", type="string", length=255)
     */
    private $data;

    public function __construct($account)
    {
        $this->account = $account;
        $this->data    = '4';
    }

    /**
     * @return DDC7140Account
     */
    public function getAccount(): DDC7140Account
    {
        return $this->account;
    }
}

/**
 * @Entity()
 * @Table(name="ddc7140_personuser")
 */
class DDC7140PersonUser
{

    /**
     * @Id()
     * @GeneratedValue(strategy="AUTO")
     * @Column(name="id", type="integer", length=11)
     */
    private $id;

    /**
     * @ManyToOne(targetEntity=DDC7140Person::class)
     * @JoinColumn(name="person_id", referencedColumnName="account_id")
     */
    private $person;

    public function __construct($id, $person)
    {
        $this->id     = $id;
        $this->person = $person;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPerson(): DDC7140Person
    {
        return $this->person;
    }
}
