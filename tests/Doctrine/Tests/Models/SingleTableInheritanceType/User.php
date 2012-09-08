<?php

namespace Doctrine\Tests\Models\SingleTableInheritanceType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="users")
 * @Entity
 */
class User
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
     * @OneToMany(targetEntity="UserFollowedUser", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    protected $followedUsers;

    /**
     * @var ArrayCollection $followedStructures
     * @OneToMany(targetEntity="UserFollowedStructure", mappedBy="user", cascade={"persist"}, orphanRemoval=true)
     */
    protected $followedStructures;

	public function __construct() {
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
     * @param UserFollowedUser $followedUsers
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
     * @param UserFollowedUser $followedUsers
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
     * @param UserFollowedStructure $followedStructures
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
     * @param UserFollowedStructure $followedStructures
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
