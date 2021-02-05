<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CustomType;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="customtype_parents")
 */
class CustomTypeParent
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /** @Column(type="negative_to_positive", nullable=true) */
    public $customInteger;

    /** @OneToOne(targetEntity="Doctrine\Tests\Models\CustomType\CustomTypeChild", cascade={"persist", "remove"}) */
    public $child;

    /** @ManyToMany(targetEntity="Doctrine\Tests\Models\CustomType\CustomTypeParent", mappedBy="myFriends") */
    private $friendsWithMe;

    /**
     * @ManyToMany(targetEntity="Doctrine\Tests\Models\CustomType\CustomTypeParent", inversedBy="friendsWithMe")
     * @JoinTable(
     *     name="customtype_parent_friends",
     *     joinColumns={@JoinColumn(name="customtypeparent_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="friend_customtypeparent_id", referencedColumnName="id")}
     * )
     */
    private $myFriends;

    public function __construct()
    {
        $this->friendsWithMe = new ArrayCollection();
        $this->myFriends     = new ArrayCollection();
    }

    public function addMyFriend(CustomTypeParent $friend): void
    {
        $this->getMyFriends()->add($friend);
        $friend->addFriendWithMe($this);
    }

    public function getMyFriends()
    {
        return $this->myFriends;
    }

    public function addFriendWithMe(CustomTypeParent $friend): void
    {
        $this->getFriendsWithMe()->add($friend);
    }

    public function getFriendsWithMe()
    {
        return $this->friendsWithMe;
    }
}
