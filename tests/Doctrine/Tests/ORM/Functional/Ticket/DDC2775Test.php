<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for cascade remove with class table inheritance.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class DDC2775Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->setUpEntitySchema(array(
            'Doctrine\Tests\ORM\Functional\Ticket\User',
            'Doctrine\Tests\ORM\Functional\Ticket\Role',
            'Doctrine\Tests\ORM\Functional\Ticket\AdminRole',
            'Doctrine\Tests\ORM\Functional\Ticket\Authorization',
        ));
    }

    /**
     * @group DDC-2775
     */
    public function testIssueCascadeRemove()
    {
        $user = new User();

        $role = new AdminRole();
        $user->addRole($role);

        $authorization = new Authorization();
        $user->addAuthorization($authorization);
        $role->addAuthorization($authorization);

        $this->_em->persist($user);
        $this->_em->flush();

        // Need to clear so that associations are lazy-loaded
        $this->_em->clear();

        $user = $this->_em->find('Doctrine\Tests\ORM\Functional\Ticket\User', $user->id);

        $this->_em->remove($user);
        $this->_em->flush();

        // With the bug, the second flush throws an error because the cascade remove didn't work correctly
        $this->_em->flush();
    }
}

/**
 * @Entity @Table(name="ddc2775_role")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="role_type", type="string")
 * @DiscriminatorMap({"admin"="AdminRole"})
 */
abstract class Role
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="roles")
     */
    public $user;

    /**
     * @OneToMany(targetEntity="Authorization", mappedBy="role", cascade={"all"}, orphanRemoval=true)
     */
    public $authorizations;

    public function addAuthorization(Authorization $authorization)
    {
        $this->authorizations[] = $authorization;
        $authorization->role = $this;
    }
}

/** @Entity @Table(name="ddc2775_admin_role") */
class AdminRole extends Role
{
}

/**
 * @Entity @Table(name="ddc2775_authorizations")
 */
class Authorization
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="User", inversedBy="authorizations")
     */
    public $user;

    /**
     * @ManyToOne(targetEntity="Role", inversedBy="authorizations")
     */
    public $role;
}

/**
 * @Entity @Table(name="ddc2775_users")
 */
class User
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @OneToMany(targetEntity="Role", mappedBy="user", cascade={"all"}, orphanRemoval=true)
     */
    public $roles;

    /**
     * @OneToMany(targetEntity="Authorization", mappedBy="user", cascade={"all"}, orphanRemoval=true)
     */
    public $authorizations;

    public function addRole(Role $role)
    {
        $this->roles[] = $role;
        $role->user = $this;
    }

    public function addAuthorization(Authorization $authorization)
    {
        $this->authorizations[] = $authorization;
        $authorization->user = $this;
    }
}
