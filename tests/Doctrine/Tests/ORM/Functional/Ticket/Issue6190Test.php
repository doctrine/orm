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
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(Issue6190User::CLASSNAME),
            $this->_em->getClassMetadata(Issue6190Group::CLASSNAME),
        ));
    }

    public function testManyToManyCollectionItemsArePreserved()
    {
        $user  = new Issue6190User();
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
 * @Entity
 */
class Issue6190User
{
    const CLASSNAME = __CLASS__;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ManyToMany(targetEntity="Issue6190Group", inversedBy="users")
     */
    private $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
}

/**
 * @Table
 * @Entity
 */
class Issue6190Group
{
    const CLASSNAME = __CLASS__;

    /**
     * @var integer
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ManyToMany(targetEntity="Issue6190User", mappedBy="groups")
     */
    public $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
}