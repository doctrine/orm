<?php

namespace Doctrine\Tests\Models\Generic;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="decimal_model")
 */
class DecimalModel
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @ORM\Column(name="decimal", type="decimal", scale=2, precision=5)
     */
    public $decimal;

    /**
     * @ORM\Column(name="high_scale", type="decimal", scale=4, precision=14)
     */
    public $highScale;
}