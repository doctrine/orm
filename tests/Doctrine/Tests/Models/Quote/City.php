<?php

namespace Doctrine\Tests\Models\Quote;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="quote-city")
 */
class City
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="city-id")
     */
    public $id;

    /**
     * @ORM\Column(name="city-name")
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
