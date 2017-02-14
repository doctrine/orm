<?php

namespace Doctrine\Tests\Models\Quote;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="table")
 */
class NumericEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="1:1")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string", name="2:2")
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