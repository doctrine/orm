<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Forum;

/**
 * @Entity
 * @Table(name="forum_avatars")
 */
class ForumAvatar
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}
