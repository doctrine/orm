<?php

namespace Doctrine\Tests\Models\Forum;

/**
 * @DoctrineEntity(tableName="forum_users")
 * @DoctrineInheritanceType("joined")
 * @DoctrineDiscriminatorColumn(name="dtype", type="varchar", length=20)
 * @DoctrineDiscriminatorMap({
        "user" = "Doctrine\Tests\Models\Forum\ForumUser",
        "admin" = "Doctrine\Tests\Models\Forum\ForumAdministrator"})
 * @DoctrineSubclasses({"Doctrine\Tests\Models\Forum\ForumAdministrator"})
 */
class ForumUser
{
    /**
     * @DoctrineColumn(type="integer")
     * @DoctrineId
     * @DoctrineIdGenerator("auto")
     */
    public $id;
    /**
     * @DoctrineColumn(type="varchar", length=50)
     */
    public $username;
    /**
     * @DoctrineOneToOne(
           targetEntity="Doctrine\Tests\Models\Forum\ForumAvatar",
           joinColumns={"avatar_id" = "id"},
           cascade={"save"})
     */
    public $avatar;
}