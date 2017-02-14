<?php

namespace Doctrine\Tests\Models\Generic;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="boolean_model")
 */
class BooleanModel
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\Column(type="boolean")
     */
    public $booleanField;
}