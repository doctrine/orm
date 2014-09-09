<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Functional tests for cascade remove without orphanRemoval.
 *
 * @author Lallement Thomas <thomas.lallement@9online.fr>
 */
class DDC0001Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema(array(
            'Doctrine\Tests\ORM\Functional\Ticket\DDC0001_User',
            'Doctrine\Tests\ORM\Functional\Ticket\DDC0001_Role',
            'Doctrine\Tests\ORM\Functional\Ticket\DDC0001_ActionLog',
        ));
    }

    public function testIssueCascadeRemoveNotOrphanRemoval()
    {
        $user = new DDC0001_User();
        $role = new DDC0001_Role();

        $user->addRole($role);

        $this->_em->persist($user);
        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC0001_User', $user->id);
        $role = $user->roles->get(0);

        $this->assertEquals(1, count($user->roles));

        $user->roles->removeElement($role);
        $this->_em->remove($role);

        $this->assertEquals(0, count($user->roles));

        $this->_em->flush();
        $this->_em->clear();

        $user = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC0001_User', $user->id);

        $this->assertEquals(0, count($user->roles));

        $actionLog = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\DDC0001_ActionLog', 1);

        $this->assertEquals('1 - remove', $actionLog->id.' - '.$actionLog->action);
    }
}

/**
 * @Entity @Table(name="ddc0001_role") @HasLifecycleCallbacks
 */
class DDC0001_Role
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="DDC0001_User", inversedBy="roles")
     */
    public $user;

    /**
     * @PreRemove
     */
    public function preRemove(LifecycleEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();

        $actionLog = new DDC0001_ActionLog();
        $actionLog->action = 'remove';
        $em->persist($actionLog);
    }
}

/**
 * @Entity @Table(name="ddc0001_user")
 */
class DDC0001_User
{
    public $changeSet = array();

    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @OneToMany(targetEntity="DDC0001_Role", mappedBy="user", cascade={"all"})
     */
    public $roles;

    public function addRole(DDC0001_Role $role)
    {
        $this->roles[] = $role;
        $role->user = $this;
    }
}

/**
 * @Entity @Table(name="ddc0001_action_log")
 */
class DDC0001_ActionLog
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
    */
    public $id;

    /**
     * @Column(name="action", type="string", length=255, nullable=true)
     */
    public $action;
}
