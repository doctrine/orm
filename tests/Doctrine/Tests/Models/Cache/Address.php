<?php

namespace Doctrine\Tests\Models\Cache;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("cache_client_address")
 */
class Address
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\JoinColumn(name="person_id", referencedColumnName="id")
     * @ORM\OneToOne(targetEntity="Person", inversedBy="address")
     */
    public $person;

    /**
     * @ORM\Column
     */
    public $location;

    public function __construct($location)
    {
        $this->location = $location;
    }
}
