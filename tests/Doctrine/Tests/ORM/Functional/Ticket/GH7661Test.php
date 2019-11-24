<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use function array_keys;

/**
 * @group GH-7661
 */
class GH7661Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH7661User::class,
            GH7661Event::class,
            GH7661Participant::class,
        ]);

        $u1 = new GH7661User();
        $u2 = new GH7661User();
        $e  = new GH7661Event();
        $this->em->persist($u1);
        $this->em->persist($u2);
        $this->em->persist($e);
        $this->em->persist(new GH7661Participant($u1, $e));
        $this->em->persist(new GH7661Participant($u2, $e));
        $this->em->flush();
        $this->em->clear();
    }

    public function testIndexByAssociation() : void
    {
        $e    = $this->em->find(GH7661Event::class, 1);
        $keys = $e->participants->getKeys();
        self::assertEquals([1, 2], $keys);

        $participants = $this->em->createQuery('SELECT p FROM ' . GH7661Participant::class . ' p INDEX BY p.user')->getResult();
        $keys         = array_keys($participants);
        self::assertEquals([1, 2], $keys);
    }
}

/**
 * @ORM\Entity
 */
class GH7661User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
}

/**
 * @ORM\Entity
 */
class GH7661Event
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
    /** @ORM\OneToMany(targetEntity=GH7661Participant::class, mappedBy="event", indexBy="user_id") */
    public $participants;
}

/**
 * @ORM\Entity
 */
class GH7661Participant
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
    /** @ORM\ManyToOne(targetEntity=GH7661User::class) */
    public $user;
    /** @ORM\ManyToOne(targetEntity=GH7661Event::class) */
    public $event;

    public function __construct(GH7661User $user, GH7661Event $event)
    {
        $this->user  = $user;
        $this->event = $event;
    }
}
