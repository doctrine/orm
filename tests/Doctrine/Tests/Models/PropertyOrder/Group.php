<?php

namespace Doctrine\Tests\Models\PropertyOrder;

/**
 * @Entity
 */
class Group
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
}
