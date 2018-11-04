<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use function reset;

class DDC2138Test extends OrmFunctionalTestCase
{
    /**
     * @group DDC-2138
     */
    public function testForeignKeyOnSTIWithMultipleMapping() : void
    {
        $schema = $this->schemaTool->getSchemaFromMetadata(
            [
                $this->em->getClassMetadata(DDC2138User::class),
                $this->em->getClassMetadata(DDC2138Structure::class),
                $this->em->getClassMetadata(DDC2138UserFollowedObject::class),
                $this->em->getClassMetadata(DDC2138UserFollowedStructure::class),
                $this->em->getClassMetadata(DDC2138UserFollowedUser::class),
            ]
        );

        self::assertTrue($schema->hasTable('users_followed_objects'), 'Table users_followed_objects should exist.');

        /** @var Table $table */
        $table = $schema->getTable('users_followed_objects');

        self::assertTrue($table->columnsAreIndexed(['object_id']));
        self::assertTrue($table->columnsAreIndexed(['user_id']));

        $foreignKeys = $table->getForeignKeys();

        self::assertCount(1, $foreignKeys, 'user_id column has to have FK, but not object_id');

        /** @var ForeignKeyConstraint $fk */
        $fk = reset($foreignKeys);

        self::assertEquals('users', $fk->getForeignTableName());

        $localColumns = $fk->getLocalColumns();

        self::assertContains('"user_id"', $localColumns);
        self::assertCount(1, $localColumns);
    }
}



/**
 * @ORM\Table(name="structures")
 * @ORM\Entity
 */
class DDC2138Structure
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @ORM\Column(type="string", length=32, nullable=true) */
    protected $name;
}

/**
 * @ORM\Entity
 * @ORM\Table(name="users_followed_objects")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="object_type", type="smallint")
 * @ORM\DiscriminatorMap({4 = DDC2138UserFollowedUser::class, 3 = DDC2138UserFollowedStructure::class})
 */
abstract class DDC2138UserFollowedObject
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int $id
     */
    protected $id;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}

/**
 * @ORM\Entity
 */
class DDC2138UserFollowedStructure extends DDC2138UserFollowedObject
{
    /**
     * @ORM\ManyToOne(targetEntity=DDC2138User::class, inversedBy="followedStructures")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @var User $user
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity=DDC2138Structure::class)
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", nullable=false)
     *
     * @var Structure $followedStructure
     */
    private $followedStructure;

    /**
     * Construct a UserFollowedStructure entity
     */
    public function __construct(User $user, Structure $followedStructure)
    {
        $this->user              = $user;
        $this->followedStructure = $followedStructure;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Gets followed structure
     *
     * @return Structure
     */
    public function getFollowedStructure()
    {
        return $this->followedStructure;
    }
}

/**
 * @ORM\Entity
 */
class DDC2138UserFollowedUser extends DDC2138UserFollowedObject
{
    /**
     * @ORM\ManyToOne(targetEntity=DDC2138User::class, inversedBy="followedUsers")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     *
     * @var User $user
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity=DDC2138User::class)
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", nullable=false)
     *
     * @var User $user
     */
    private $followedUser;

    /**
     * Construct a UserFollowedUser entity
     *
     * @param bool $giveAgency
     */
    public function __construct(User $user, User $followedUser)
    {
        $this->user         = $user;
        $this->followedUser = $followedUser;
    }

    /**
     * {@inheritdoc}
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Gets followed user
     *
     * @return User
     */
    public function getFollowedUser()
    {
        return $this->followedUser;
    }
}

/**
 * @ORM\Table(name="users")
 * @ORM\Entity
 */
class DDC2138User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @ORM\Column(type="string", length=32, nullable=true) */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity=DDC2138UserFollowedUser::class, mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     *
     * @var ArrayCollection $followedUsers
     */
    protected $followedUsers;

    /**
     * @ORM\OneToMany(targetEntity=DDC2138UserFollowedStructure::class, mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     *
     * @var ArrayCollection $followedStructures
     */
    protected $followedStructures;

    public function __construct()
    {
        $this->followedUsers      = new ArrayCollection();
        $this->followedStructures = new ArrayCollection();
    }

    /**
     * Remove followers
     */
    public function removeFollower(UserFollowedUser $followers)
    {
        $this->followers->removeElement($followers);
    }

    /**
     * Add followedUsers
     *
     * @return User
     */
    public function addFollowedUser(UserFollowedUser $followedUsers)
    {
        $this->followedUsers[] = $followedUsers;

        return $this;
    }

    /**
     * Remove followedUsers
     *
     * @return User
     */
    public function removeFollowedUser(UserFollowedUser $followedUsers)
    {
        $this->followedUsers->removeElement($followedUsers);

        return $this;
    }

    /**
     * Get followedUsers
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getFollowedUsers()
    {
        return $this->followedUsers;
    }

    /**
     * Add followedStructures
     *
     * @return User
     */
    public function addFollowedStructure(UserFollowedStructure $followedStructures)
    {
        $this->followedStructures[] = $followedStructures;

        return $this;
    }

    /**
     * Remove followedStructures
     *
     * @return User
     */
    public function removeFollowedStructure(UserFollowedStructure $followedStructures)
    {
        $this->followedStructures->removeElement($followedStructures);

        return $this;
    }

    /**
     * Get followedStructures
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getFollowedStructures()
    {
        return $this->followedStructures;
    }
}
