<?php

namespace Doctrine\Tests\Models\Timestampable;

/**
 * @Entity
 * @Table(name="timestampable_user")
 */
class User
{
    use Timestampable;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string", nullable=true)
     */
    public $name;
}
