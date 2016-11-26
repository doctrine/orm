<?php

namespace Doctrine\Tests\Models\GeneratedValueCustomType;

/**
 * @Entity
 * @Table(name="custom_id_user")
 */
class GeneratedValueCustomTypeUser
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="GeneratedValueCustomIdObject")
     */
    public $id;

    /**
     * @Column
     */
    public $username;

    public function __construct($username)
    {
        $this->username = $username;
    }
}
