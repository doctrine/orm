<?php

namespace Doctrine\Tests\Models\Generic;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="serialize_model")
 */
class SerializationModel
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\Column(name="the_array", type="array", nullable=true)
     */
    public $array;

    /**
     * @ORM\Column(name="the_obj", type="object", nullable=true)
     */
    public $object;
}