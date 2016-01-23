<?php

namespace Shitty\Tests\Models\Forum;

/**
 * @Entity
 */
class ForumAdministrator extends ForumUser
{
    /**
     * @Column(type="integer", name="access_level")
     */
    public $accessLevel;
}