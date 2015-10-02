<?php

namespace Doctrine\Tests\Models\Pagination\JoinedInheritance;

/**
 * @package Doctrine\Tests\Models\Pagination\JoinedInheritance
 *
 * @Entity
 * @Table(name="pagination_joined_user_client")
 */
class UserClientEntity extends UserMainEntity
{
    /**
     * @Column(type="string")
     */
    public $full_name;
}
