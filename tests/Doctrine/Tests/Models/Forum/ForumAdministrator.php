<?php

namespace Doctrine\Tests\Models\Forum;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class ForumAdministrator extends ForumUser
{
    /**
     * @ORM\Column(type="integer", name="access_level")
     */
    public $accessLevel;
}