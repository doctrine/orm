<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * @Entity
 * @Table(name="forum_entries")
 */
class ForumEntry
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="auto")
     */
    public $id;
    /**
     * @Column(type="string", length=50)
     */
    public $topic;
}

