<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group issue-6190
 */
class Issue6190Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->useModelSet('issue5989');
        parent::setUp();
    }

    public function testManyToManyCollectionItemsArePreserved()
    {
        $user = new Issue6190User();

        $group = new Issue6190Group();

        $this->_em->persist($user);
        $this->_em->persist($group);
        $this->_em->flush();

        /* @var $user Issue6190User */
        $user = $this->_em->find(Issue6190User::CLASSNAME, $user->id);
        /* @var $group Issue6190Group */
        $group = $this->_em->find(Issue6190Group::CLASSNAME, $group->id);

        $group->users->add($user);

        $this->_em->flush();

        self::assertCount(1, $group->users);
    }
}

/**
 * @ORM\Entity
 */
class Issue6190User
{
    const CLASSNAME = __CLASS__;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity=Issue6190Group::CLASSNAME, inversedBy="users")
     */
    private $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
}

/**
 * @ORM\Table
 * @ORM\Entity
 */
class Issue6190Group
{
    const CLASSNAME = __CLASS__;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity=Issue6190User::CLASSNAME, mappedBy="groups")
     */
    public $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
}