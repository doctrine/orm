<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * @DoctrineEntity
 */
class ForumAdministrator extends ForumUser
{
    /**
     * @DoctrineColumn(type="integer", name="access_level")
     */
    public $accessLevel;
}