<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10483Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH10483Role::class,
            GH10483User::class
        );
    }

    public function testFlushChangedUserAfterRoleHasBeenDeleted(): void
    {
        $em = $this->_em;

        $role       = new GH10483Role();
        $role->name = 'test';
        $em->persist($role);

        $user       = new GH10483User();
        $user->name = 'test';
        $user->roles->add($role);
        $em->persist($user);

        $em->flush();

        self::assertFalse($user->roles->isDirty());

        $em->remove($role);
        $em->flush();

        // UnitOfWork::computeAssociationChanges(), lines 968 ff. will remove the removed entity from the collection:
        self::assertEmpty($user->roles);

        // The UoW left the collection in a dirty state, is that correct?
        self::assertTrue($user->roles->isDirty()); // <-- might need to assert "false" (?)

        // The collection's snapshot still contains the removed $role entity, is that correct?
        self::assertSame([$role], $user->roles->getSnapshot()); // <-- might need to assert snapshot being empty (?)

        // Since the collection is dirty and/or has a snapshot that differs from the state,
        // this flush will try to remove the $role from the collection, and fails when looking for
        // it in the identity map
        $em->flush();
    }
}

/**
 * @ORM\Entity
 */
class GH10483Role
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\Column
     *
     * @var string
     */
    public $name;
}

/**
 * @ORM\Entity
 */
class GH10483User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\Column
     *
     * @var string
     */
    public $name;

    /**
     * @ORM\ManyToMany(targetEntity="GH10483Role")
     *
     * @var Collection
     */
    public $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }
}
