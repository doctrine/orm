<?php

namespace Doctrine\Tests\Models\Issue5989;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="issue5989_employees")
 */
class Issue5989Employee extends Issue5989Person
{
    /**
     * @ORM\Column(type="simple_array", nullable=true)
     *
     * @var array
     */
    public $tags;
}
