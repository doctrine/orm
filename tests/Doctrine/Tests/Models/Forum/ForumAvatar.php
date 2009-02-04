<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * @DoctrineEntity
 * @DoctrineTable(name="forum_avatars")
 */
class ForumAvatar
{
    /**
     * @DoctrineId
     * @DoctrineColumn(type="integer")
     * @DoctrineIdGenerator("auto")
     */
    public $id;
}
