<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
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

        $this->setUpEntitySchema(
            [
                User::class,
                Role::class,
                AdminRole::class,
                Authorization::class,
            ]
        );
    }

    /**
     * @group DDC-2775
     */
    public function testIssueCascadeRemove()
    {
        $role = new AdminRole();
        $user = new User();
        $user->addRole($role);

        $authorization = new Authorization();
        $user->addAuthorization($authorization);
        $role->addAuthorization($authorization);

        $this->em->persist($user);
        $this->em->flush();

        // Need to clear so that associations are lazy-loaded
        $this->em->clear();

        $user = $this->em->find(User::class, $user->id);

        $this->em->remove($user);
        $this->em->flush();

        self::assertEmpty($this->_em->getRepository(Authorization::class)->findAll());

        // With the bug, the second flush throws an error because the cascade remove didn't work correctly
        $this->em->flush();
    }
}

/**
 * @ORM\Entity @ORM\Table(name="ddc2775_role")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="role_type", type="string")
 * @ORM\DiscriminatorMap({"admin"="AdminRole"})
 */
abstract class Role
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="roles")
     */
    public $user;

    /**
     * @ORM\OneToMany(targetEntity="Authorization", mappedBy="role", cascade={"all"}, orphanRemoval=true)
     */
    public $authorizations;

    public function addAuthorization(Authorization $authorization)
    {
        $this->authorizations[] = $authorization;
        $authorization->role = $this;
    }
}

/** @ORM\Entity @ORM\Table(name="ddc2775_admin_role") */
class AdminRole extends Role
{
}

/**
 * @ORM\Entity @ORM\Table(name="ddc2775_authorizations")
 */
class Authorization
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="authorizations")
     */
    public $user;

    /**
     * @ORM\ManyToOne(targetEntity="Role", inversedBy="authorizations")
     */
    public $role;
}

/**
 * @ORM\Entity @ORM\Table(name="ddc2775_users")
 */
class User
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="Role", mappedBy="user", cascade={"all"}, orphanRemoval=true)
     */
    public $roles;

    /**
     * @ORM\OneToMany(targetEntity="Authorization", mappedBy="user", cascade={"all"}, orphanRemoval=true)
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
