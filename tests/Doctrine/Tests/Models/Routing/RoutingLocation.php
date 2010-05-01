<?php

namespace Doctrine\Tests\Models\Routing;

/**
 * @Entity
 */
class RoutingLocation
{
    /**
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    public function getName()
    {
        return $this->name;
    }
}