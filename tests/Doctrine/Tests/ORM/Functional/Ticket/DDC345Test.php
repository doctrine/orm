<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC345Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC345User::class),
                $this->em->getClassMetadata(DDC345Group::class),
                $this->em->getClassMetadata(DDC345Membership::class),
            ]
        );
    }

    public function testTwoIterateHydrations() : void
    {
        // Create User
        $user       = new DDC345User();
        $user->name = 'Test User';
        $this->em->persist($user); // $em->flush() does not change much here

        // Create Group
        $group       = new DDC345Group();
        $group->name = 'Test Group';
        $this->em->persist($group); // $em->flush() does not change much here

        $membership        = new DDC345Membership();
        $membership->group = $group;
        $membership->user  = $user;
        $membership->state = 'active';

        //$this->em->persist($membership); // COMMENT OUT TO SEE BUG
        /*
        This should be not necessary, but without, its PrePersist is called twice,
        $membership seems to be persisted twice, but all properties but the
        ones set by LifecycleCallbacks are deleted.
        */

        $user->Memberships->add($membership);
        $group->Memberships->add($membership);

        $this->em->flush();

        self::assertEquals(1, $membership->prePersistCallCount);
        self::assertEquals(0, $membership->preUpdateCallCount);
        self::assertInstanceOf('DateTime', $membership->updated);
    }
}

/**
 * @ORM\Entity
 */
class DDC345User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\Column(type="string") */
    public $name;

    /** @ORM\OneToMany(targetEntity=DDC345Membership::class, mappedBy="user", cascade={"persist"}) */
    public $Memberships;

    public function __construct()
    {
        $this->Memberships = new ArrayCollection();
    }
}

/**
 * @ORM\Entity
 */
class DDC345Group
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /** @ORM\Column(type="string") */
    public $name;

    /** @ORM\OneToMany(targetEntity=DDC345Membership::class, mappedBy="group", cascade={"persist"}) */
    public $Memberships;


    public function __construct()
    {
        $this->Memberships = new ArrayCollection();
    }
}

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="ddc345_memberships", uniqueConstraints={
 *      @ORM\UniqueConstraint(name="ddc345_memship_fks", columns={"user_id","group_id"})
 * })
 */
class DDC345Membership
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity=DDC345User::class, inversedBy="Memberships")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    public $user;

    /**
     * @ORM\OneToOne(targetEntity=DDC345Group::class, inversedBy="Memberships")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", nullable=false)
     */
    public $group;

    /** @ORM\Column(type="string") */
    public $state;

    /** @ORM\Column(type="datetime") */
    public $updated;

    public $prePersistCallCount = 0;
    public $preUpdateCallCount  = 0;

    /** @ORM\PrePersist */
    public function doStuffOnPrePersist()
    {
        //echo "***** PrePersist\n";
        ++$this->prePersistCallCount;
        $this->updated = new DateTime();
    }

    /** @ORM\PreUpdate */
    public function doStuffOnPreUpdate()
    {
        //echo "***** PreUpdate\n";
        ++$this->preUpdateCallCount;
        $this->updated = new DateTime();
    }
}
