<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Forum;

/**
 * @Entity
 * @Table(name="forum_entries")
 */
class ForumEntry
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @var string
     * @Column(type="string", length=50)
     */
    public $topic;

    public function &getTopicByReference(): string
    {
        return $this->topic;
    }
}
