<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Functional tests for cascade remove with class table inheritance.
 */
class DDC2775Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                User::class,
                Role::class,
                AdminRole::class,
                Authorization::class,
            ],
        );
    }

    #[Group('DDC-2775')]
    public function testIssueCascadeRemove(): void
    {
        $role = new AdminRole();
        $user = new User();
        $user->addRole($role);

        $authorization = new Authorization();
        $user->addAuthorization($authorization);
        $role->addAuthorization($authorization);

        $this->_em->persist($user);
        $this->_em->flush();

        // Need to clear so that associations are lazy-loaded
        $this->_em->clear();

        $user = $this->_em->find(User::class, $user->id);

        $this->_em->remove($user);
        $this->_em->flush();

        self::assertEmpty($this->_em->getRepository(Authorization::class)->findAll());

        // With the bug, the second flush throws an error because the cascade remove didn't work correctly
        $this->_em->flush();
    }
}

#[Table(name: 'ddc2775_role')]
#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'role_type', type: 'string')]
#[DiscriminatorMap(['admin' => 'AdminRole'])]
abstract class Role
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var User */
    #[ManyToOne(targetEntity: 'User', inversedBy: 'roles')]
    public $user;

    /** @phpstan-var Collection<int, Authorization> */
    #[OneToMany(targetEntity: 'Authorization', mappedBy: 'role', cascade: ['all'], orphanRemoval: true)]
    public $authorizations;

    public function addAuthorization(Authorization $authorization): void
    {
        $this->authorizations[] = $authorization;
        $authorization->role    = $this;
    }
}

#[Table(name: 'ddc2775_admin_role')]
#[Entity]
class AdminRole extends Role
{
}

#[Table(name: 'ddc2775_authorizations')]
#[Entity]
class Authorization
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var User */
    #[ManyToOne(targetEntity: 'User', inversedBy: 'authorizations')]
    public $user;

    /** @var Role */
    #[ManyToOne(targetEntity: 'Role', inversedBy: 'authorizations')]
    public $role;
}

#[Table(name: 'ddc2775_users')]
#[Entity]
class User
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @phpstan-var Collection<int, Role> */
    #[OneToMany(targetEntity: 'Role', mappedBy: 'user', cascade: ['all'], orphanRemoval: true)]
    public $roles;

    /** @phpstan-var Collection<int, Authorization> */
    #[OneToMany(targetEntity: 'Authorization', mappedBy: 'user', cascade: ['all'], orphanRemoval: true)]
    public $authorizations;

    public function addRole(Role $role): void
    {
        $this->roles[] = $role;
        $role->user    = $this;
    }

    public function addAuthorization(Authorization $authorization): void
    {
        $this->authorizations[] = $authorization;
        $authorization->user    = $this;
    }
}
