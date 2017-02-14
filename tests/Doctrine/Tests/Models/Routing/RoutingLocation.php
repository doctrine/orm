<?php

namespace Doctrine\Tests\Models\Routing;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class RoutingLocation
{
    /**
     * @ORM\Id @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    public function getName()
    {
        return $this->name;
    }
}