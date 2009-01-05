<?php

#namespace Doctrine\Tests\Models\Forum;

/**
 * @DoctrineEntity
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
