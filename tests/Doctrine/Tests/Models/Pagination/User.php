<?php

namespace Doctrine\Tests\Models\Pagination;


/**
 * @package Doctrine\Tests\Models\Pagination
 *
 * @Entity
 * @Table(name="pagination_user")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"user1"="User1"})
 */
abstract class User
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @Column(type="string")
     */
    public $name;
}
