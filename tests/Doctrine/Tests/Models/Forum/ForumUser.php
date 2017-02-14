<?php

namespace Doctrine\Tests\Models\Forum;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="forum_users")
 */
class ForumUser
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id @ORM\GeneratedValue
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    public $username;

    /**
     * @ORM\OneToOne(targetEntity="ForumAvatar", cascade={"persist"})
     * @ORM\JoinColumn(name="avatar_id", referencedColumnName="id")
     */
    public $avatar;

    public function getId() {
    	return $this->id;
    }

    public function getUsername() {
    	return $this->username;
    }

    public function getAvatar() {
    	return $this->avatar;
    }

    public function setAvatar(ForumAvatar $avatar) {
    	$this->avatar = $avatar;
    }
}
