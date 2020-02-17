<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use ProxyManager\Proxy\GhostObjectInterface;

class DDC881Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC881User::class),
                    $this->em->getClassMetadata(DDC881Phonenumber::class),
                    $this->em->getClassMetadata(DDC881Phonecall::class),
                ]
            );
        } catch (Exception $e) {
        }
    }

    /**
     * @group DDC-117
     * @group DDC-881
     */
    public function testIssue() : void
    {
        /* Create two test users: albert and alfons */
        $albert = new DDC881User();
        $albert->setName('albert');
        $this->em->persist($albert);

        $alfons = new DDC881User();
        $alfons->setName('alfons');
        $this->em->persist($alfons);

        $this->em->flush();

        /* Assign two phone numbers to each user */
        $phoneAlbert1 = new DDC881PhoneNumber();
        $phoneAlbert1->setUser($albert);
        $phoneAlbert1->setId(1);
        $phoneAlbert1->setPhoneNumber('albert home: 012345');
        $this->em->persist($phoneAlbert1);

        $phoneAlbert2 = new DDC881PhoneNumber();
        $phoneAlbert2->setUser($albert);
        $phoneAlbert2->setId(2);
        $phoneAlbert2->setPhoneNumber('albert mobile: 67890');
        $this->em->persist($phoneAlbert2);

        $phoneAlfons1 = new DDC881PhoneNumber();
        $phoneAlfons1->setId(1);
        $phoneAlfons1->setUser($alfons);
        $phoneAlfons1->setPhoneNumber('alfons home: 012345');
        $this->em->persist($phoneAlfons1);

        $phoneAlfons2 = new DDC881PhoneNumber();
        $phoneAlfons2->setId(2);
        $phoneAlfons2->setUser($alfons);
        $phoneAlfons2->setPhoneNumber('alfons mobile: 67890');
        $this->em->persist($phoneAlfons2);

        /* We call alfons and albert once on their mobile numbers */
        $call1 = new DDC881PhoneCall();
        $call1->setPhoneNumber($phoneAlfons2);
        $this->em->persist($call1);

        $call2 = new DDC881PhoneCall();
        $call2->setPhoneNumber($phoneAlbert2);
        $this->em->persist($call2);

        $this->em->flush();
        $this->em->clear();

        // fetch-join that foreign-key/primary-key entity association
        $dql   = 'SELECT c, p FROM ' . DDC881PhoneCall::class . ' c JOIN c.phonenumber p';
        $calls = $this->em->createQuery($dql)->getResult();

        self::assertCount(2, $calls);
        self::assertNotInstanceOf(GhostObjectInterface::class, $calls[0]->getPhoneNumber());
        self::assertNotInstanceOf(GhostObjectInterface::class, $calls[1]->getPhoneNumber());

        $dql     = 'SELECT p, c FROM ' . DDC881PhoneNumber::class . ' p JOIN p.calls c';
        $numbers = $this->em->createQuery($dql)->getResult();

        self::assertCount(2, $numbers);
        self::assertInstanceOf(PersistentCollection::class, $numbers[0]->getCalls());
        self::assertTrue($numbers[0]->getCalls()->isInitialized());
    }
}

/**
 * @ORM\Entity
 */
class DDC881User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** @ORM\Column(type="string") */
    private $name;

    /** @ORM\OneToMany(targetEntity=DDC881PhoneNumber::class,mappedBy="id") */
    private $phoneNumbers;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

/**
 * @ORM\Entity
 */
class DDC881PhoneNumber
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $id;
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity=DDC881User::class,cascade={"all"})
     */
    private $user;
    /** @ORM\Column(type="string") */
    private $phonenumber;

    /** @ORM\OneToMany(targetEntity=DDC881PhoneCall::class, mappedBy="phonenumber") */
    private $calls;

    public function __construct()
    {
        $this->calls = new ArrayCollection();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setUser(DDC881User $user)
    {
        $this->user = $user;
    }

    public function setPhoneNumber($phoneNumber)
    {
        $this->phonenumber = $phoneNumber;
    }

    public function getCalls()
    {
        return $this->calls;
    }
}

/**
 * @ORM\Entity
 */
class DDC881PhoneCall
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity=DDC881PhoneNumber::class, inversedBy="calls", cascade={"all"})
     * @ORM\JoinColumns({
     *  @ORM\JoinColumn(name="phonenumber_id", referencedColumnName="id"),
     *  @ORM\JoinColumn(name="user_id", referencedColumnName="user_id")
     * })
     */
    private $phonenumber;
    /** @ORM\Column(type="string",nullable=true) */
    private $callDate;

    public function setPhoneNumber(DDC881PhoneNumber $phoneNumber)
    {
        $this->phonenumber = $phoneNumber;
    }

    public function getPhoneNumber()
    {
        return $this->phonenumber;
    }
}
