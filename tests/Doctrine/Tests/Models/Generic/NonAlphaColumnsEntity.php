<?php

namespace Doctrine\Tests\Models\Generic;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="not-a-simple-entity")
 */
class NonAlphaColumnsEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="simple-entity-id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string", name="simple-entity-value")
     */
    public $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}