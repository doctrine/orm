<?php

namespace Doctrine\Tests\Models\DDC3899;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC3899FlexContract extends DDC3899Contract
{
    /** @ORM\Column(type="integer") */
    public $hoursWorked = 0;

    /** @ORM\Column(type="integer") */
    public $pricePerHour = 0;
}
