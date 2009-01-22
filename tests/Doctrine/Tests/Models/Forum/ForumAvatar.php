<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * @DoctrineEntity(tableName="forum_avatars")
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
