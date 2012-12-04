<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Tools\SchemaTool;

class DDC2138Test extends OrmFunctionalTestCase
{
    /**
     * @group DDC-2138
     */
    public function testForeignKeyOnSTIWithMultipleMapping()
    {
        $em = $this->_em;
        $schemaTool = new SchemaTool($em);

        $classes = array(
            $em->getClassMetadata(__NAMESPACE__ . '\DDC2138User'),
            $em->getClassMetadata(__NAMESPACE__ . '\DDC2138Structure'),
            $em->getClassMetadata(__NAMESPACE__ . '\DDC2138UserFollowedObject'),
            $em->getClassMetadata(__NAMESPACE__ . '\DDC2138UserFollowedStructure'),
            $em->getClassMetadata(__NAMESPACE__ . '\DDC2138UserFollowedUser')
        );

        $schema = $schemaTool->getSchemaFromMetadata($classes);
        $this->assertTrue($schema->hasTable('users_followed_objects'), "Table users_followed_objects should exist.");

        /* @var $table \Doctrine\DBAL\Schema\Table */
        $table = ($schema->getTable('users_followed_objects'));
        $this->assertTrue($table->columnsAreIndexed(array('object_id')));
        $this->assertTrue($table->columnsAreIndexed(array('user_id')));
        $foreignKeys = $table->getForeignKeys();
        $this->assertCount(1, $foreignKeys, 'user_id column has to have FK, but not object_id');

        /* @var $fk \Doctrine\DBAL\Schema\ForeignKeyConstraint */
        $fk = reset($foreignKeys);
        $this->assertEquals('users', $fk->getForeignTableName());

        $localColumns = $fk->getLocalColumns();
        $this->assertContains('user_id', $localColumns);
        $this->assertCount(1, $localColumns);
    }
}



/**
 * @Table(name="structures")
 * @Entity
 */
class DDC2138Structure
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=32, nullable=true)
     */
    protected $name;
}

/**
 * @Entity
 * @Table(name="users_followed_objects")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="object_type", type="smallint")
 * @DiscriminatorMap({4 = "DDC2138UserFollowedUser", 3 = "DDC2138UserFollowedStructure"})
 */
abstract class DDC2138UserFollowedObject
{
    /**
     * @var integer $id
     *
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}

/**
 * @Entity
 */
class DDC2138UserFollowedStructure extends DDC2138UserFollowedObject
{
    /**
     * @ManyToOne(targetEntity="DDC2138User", inversedBy="followedStructures")
     * @JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @var User $user
     */
    protected $user;

    /**
     * @ManyToOne(targetEntity="DDC2138Structure")
     * @JoinColumn(name="object_id", referencedColumnName="id", nullable=false)
     * @var Structure $followedStructure
     */
    private $followedStructure;

    /**
     * Construct a UserFollowedStructure entity
     *
     * @param User      $user
     * @param Structure $followedStructure
     */
    public function __construct(User $user, Structure $followedStructure)
    {
        $this->user = $user;
        $this->followedStructure = $followedStructure;
    }

    /**
     *
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
 * @Entity
 */
class DDC2138UserFollowedUser extends DDC2138UserFollowedObject
{
    /**
     * @ManyToOne(targetEntity="DDC2138User", inversedBy="followedUsers")
     * @JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @var User $user
     */
    protected $user;

    /**
     * @ManyToOne(targetEntity="DDC2138User")
     * @JoinColumn(name="object_id", referencedColumnName="id", nullable=false)
     * @var User $user
     */
    private $followedUser;

    /**
     * Construct a UserFollowedUser entity
     *
     * @param User $user
     * @param User $followedUser
     * @param bool $giveAgency
     */
    public function __construct(User $user, User $followedUser)
    {
        $this->user = $user;
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
 * @Table(name="users")
 * @Entity
 */
class DDC2138User
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=32, nullable=true)
     */
    protected $name;

    /**
     * @var ArrayCollection $followedUsers
     * @OneToMany(targetEntity="DDC2138UserFollowedUser", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    protected $followedUsers;

    /**
     * @var ArrayCollection $followedStructures
     * @OneToMany(targetEntity="DDC2138UserFollowedStructure", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    protected $followedStructures;

    public function __construct()
    {
        $this->followedUsers = new ArrayCollection();
        $this->followedStructures = new ArrayCollection();
    }

    /*
     * Remove followers
     *
     * @param UserFollowedUser $followers
     */
    private function removeFollower(UserFollowedUser $followers)
    {
        $this->followers->removeElement($followers);
    }

    /**
     * Add followedUsers
     *
     * @param  UserFollowedUser $followedUsers
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
     * @param  UserFollowedUser $followedUsers
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
     * @param  UserFollowedStructure $followedStructures
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
     * @param  UserFollowedStructure $followedStructures
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
