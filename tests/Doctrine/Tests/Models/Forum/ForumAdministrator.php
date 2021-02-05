<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Forum;

/**
 * @Entity
 */
class ForumAdministrator extends ForumUser
{
    /** @Column(type="integer", name="access_level") */
    public $accessLevel;
}
