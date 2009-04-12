<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * @DoctrineEntity
 * @DoctrineTable(name="forum_users")
 */
class ForumUser
{
    /**
     * @DoctrineColumn(type="integer")
     * @DoctrineId
     * @DoctrineGeneratedValue(strategy="auto")
     */
    public $id;
    /**
     * @DoctrineColumn(type="varchar", length=50)
     */
    public $username;
    /**
     * @DoctrineOneToOne(targetEntity="ForumAvatar", cascade={"save"})
     * @DoctrineJoinColumn(name="avatar_id", referencedColumnName="id")
     */
    public $avatar;
}