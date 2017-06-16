<?php

namespace Doctrine\Tests\Models\PropertyOrder;

/**
 * @Entity
 */
class User
{
    /**
     * @Column(type="integer")
     * @Id
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @ManyToOne(targetEntity=Group::class)
     */
    public $group;
}
