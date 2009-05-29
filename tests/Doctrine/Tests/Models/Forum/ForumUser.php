<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * @Entity
 * @Table(name="forum_users")
 */
class ForumUser
{
    /**
     * @Column(type="integer")
     * @Id
     * @GeneratedValue(strategy="auto")
     */
    public $id;
    /**
     * @Column(type="string", length=50)
     */
    public $username;
    /**
     * @OneToOne(targetEntity="ForumAvatar", cascade={"save"})
     * @JoinColumn(name="avatar_id", referencedColumnName="id")
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
    
    public function setAvatar(CmsAvatar $avatar) {
    	$this->avatar = $avatar;
    }
}