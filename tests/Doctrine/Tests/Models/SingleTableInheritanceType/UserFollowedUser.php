<?php

namespace Doctrine\Tests\Models\SingleTableInheritanceType;

/**
 * @Entity
 */
class UserFollowedUser extends UserFollowedObject
{
    /**
     * @ManyToOne(targetEntity="User", inversedBy="followedUsers")
     * @JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     * @var User $user
     */
    protected $user;

    /**
     * @ManyToOne(targetEntity="User")
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
