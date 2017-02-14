<?php

namespace Doctrine\Tests\Models\Tweet;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tweet_user_list")
 */
class UserList
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $listName;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userLists")
     */
    public $owner;
}
