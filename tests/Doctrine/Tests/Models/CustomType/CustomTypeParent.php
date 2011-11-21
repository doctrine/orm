<?php

namespace Doctrine\Tests\Models\CustomType;

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

    /**
     * @Column(type="negative_to_positive", nullable=true)
     */
    public $customInteger;

    /**
     * @OneToOne(targetEntity="Doctrine\Tests\Models\CustomType\CustomTypeChild", cascade={"persist", "remove"})
     */
    public $child;

    /**
     * @ManyToMany(targetEntity="Doctrine\Tests\Models\CustomType\CustomTypeParent", mappedBy="myFriends")
     */
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
        $this->friendsWithMe = new \Doctrine\Common\Collections\ArrayCollection();
        $this->myFriends = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function addMyFriend(CustomTypeParent $friend)
    {
        $this->getMyFriends()->add($friend);
        $friend->addFriendWithMe($this);
    }

    public function getMyFriends()
    {
        return $this->myFriends;
    }

    public function addFriendWithMe(CustomTypeParent $friend)
    {
        $this->getFriendsWithMe()->add($friend);
    }

    public function getFriendsWithMe()
    {
        return $this->friendsWithMe;
    }
}
