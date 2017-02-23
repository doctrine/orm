<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH-6297
 */
class GH6297Test extends OrmFunctionalTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(
            [
                $this->_em->getClassMetadata(GH6297User::class),
                $this->_em->getClassMetadata(GH6297Group::class),
            ]
        );
    }

    /**
     * @return void
     */
    public function testIssue()
    {
        $group = new GH6297Group(1, 'test');

        $user = new GH6297User(1, $group);

        $this->_em->persist($group);
        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue(true);
    }
}

/**
 * @Entity
 * @Table(name="users")
 */
class GH6297User
{
    /**
     * @Id()
     * @Column(type="integer")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="GH6297Group", inversedBy="users")
     * @JoinColumn(referencedColumnName="secondary_id", nullable=false)
     */
    public $group;

    /**
     * @param int         $id
     * @param GH6297Group $group
     */
    public function __construct($id, GH6297Group $group)
    {
        $this->id    = (int) $id;
        $this->group = $group;
    }
}

/**
 * @Entity
 * @Table(name="groups")
 */
class GH6297Group
{
    /**
     * @Id()
     * @Column(name="primary_id", type="integer")
     */
    public $primaryId;

    /**
     * @Column(name="secondary_id", type="string")
     */
    public $secondaryId;

    /**
     * @OneToMany(targetEntity="GH6297User", mappedBy="users")
     */
    public $users;

    /**
     * @param int $primaryId
     * @param int $secondaryId
     */
    public function __construct($primaryId, $secondaryId)
    {
        $this->primaryId   = (int) $primaryId;
        $this->secondaryId = (string) $secondaryId;
        $this->users       = new ArrayCollection();
    }
}
