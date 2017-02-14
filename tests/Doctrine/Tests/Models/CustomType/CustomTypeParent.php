<?php

namespace Doctrine\Tests\Models\CustomType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="customtype_parents")
 */
class CustomTypeParent
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="negative_to_positive", nullable=true)
     */
    public $customInteger;

    /**
     * @ORM\OneToOne(targetEntity="Doctrine\Tests\Models\CustomType\CustomTypeChild", cascade={"persist", "remove"})
     */
    public $child;

    /**
     * @ORM\ManyToMany(targetEntity="Doctrine\Tests\Models\CustomType\CustomTypeParent", mappedBy="myFriends")
     */
    private $friendsWithMe;

    /**
     * @ORM\ManyToMany(targetEntity="Doctrine\Tests\Models\CustomType\CustomTypeParent", inversedBy="friendsWithMe")
     * @ORM\JoinTable(
     *     name="customtype_parent_friends",
     *     joinColumns={@ORM\JoinColumn(name="customtypeparent_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="friend_customtypeparent_id", referencedColumnName="id")}
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
