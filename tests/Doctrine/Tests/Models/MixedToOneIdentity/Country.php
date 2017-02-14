<?php

namespace Doctrine\Tests\Models\MixedToOneIdentity;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class Country
{
    /** @ORM\Id @ORM\Column(type="string") @ORM\GeneratedValue(strategy="NONE") */
    public $country;
}
