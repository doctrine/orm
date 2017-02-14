<?php

namespace Doctrine\Tests\Models\Forum;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="forum_entries")
 */
class ForumEntry
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\Column(type="string", length=50)
     */
    public $topic;

    public function &getTopicByReference() {
        return $this->topic;
    }
}

