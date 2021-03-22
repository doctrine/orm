<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Routing;

/**
 * @Entity
 */
class RoutingLocation
{
    /**
     * @var int
     * @Id @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @var string
     * @Column(type="string")
     */
    public $name;

    public function getName(): string
    {
        return $this->name;
    }
}
