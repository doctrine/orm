<?php

namespace Doctrine\Tests\Models\SingleTableInheritanceType;

/**
 * @Entity
 */
class UserFollowedStructure extends UserFollowedObject
{
    /**
     * @ManyToOne(targetEntity="User", inversedBy="followedStructures")
     * @JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @var User $user
     */
    protected $user;

    /**
     * @ManyToOne(targetEntity="Structure")
     * @JoinColumn(name="object_id", referencedColumnName="id", nullable=false)
     * @var Structure $followedStructure
     */
    private $followedStructure;

    /**
     * Construct a UserFollowedStructure entity
     *
     * @param User $user
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
