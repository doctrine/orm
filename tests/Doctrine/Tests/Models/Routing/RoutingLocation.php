<?php

namespace Doctrine\Tests\Models\Routing;

/**
 * @Entity
 */
class RoutingLocation
{
    /**
     * @Id
     * @generatedValue(strategy="AUTO")
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;
}