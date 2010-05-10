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
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @Column(type="string", length=50)
     */
    public $topic;

    public function &getTopicByReference() {
        return $this->topic;
    }
}

