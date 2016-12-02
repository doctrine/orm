<?php

namespace Doctrine\Tests\Models\Pagination\JoinedInheritance;

/**
 * @package Doctrine\Tests\Models\Pagination\JoinedInheritance
 *
 * @Entity
 * @Table(name="pagination_joined_user_main")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"client"="UserClientEntity"})
 */
abstract class UserMainEntity extends UserModel
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @Column(type="datetime")
     */
    public $createdAt;

    /**
     * @Column(type="datetime")
     */
    public $updatedAt;
}
