<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Forum;

/**
 * @Entity
 * @Table(name="forum_users")
 */
class ForumUser
{
    /**
     * @var int
     * @Column(type="integer")
     * @Id @GeneratedValue
     */
    public $id;

    /**
     * @var string
     * @Column(type="string", length=50)
     */
    public $username;

    /**
     * @var ForumAvatar
     * @OneToOne(targetEntity="ForumAvatar", cascade={"persist"})
     * @JoinColumn(name="avatar_id", referencedColumnName="id")
     */
    public $avatar;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getAvatar(): ForumAvatar
    {
        return $this->avatar;
    }

    public function setAvatar(ForumAvatar $avatar): void
    {
        $this->avatar = $avatar;
    }
}
