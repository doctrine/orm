<?php

namespace Doctrine\Tests\Models\PostInsertCustomType;

/**
 * @Entity
 * @Table(name="custom_id_user")
 */
class PostInsertCustomTypeUser
{
    /**
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @Column(type="PostInsertCustomIdObject")
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
