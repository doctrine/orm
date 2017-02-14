<?php

namespace Doctrine\Tests\Models\MixedToOneIdentity;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class CompositeToOneKeyState
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue(strategy="NONE")
     */
    public $state;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Country", cascade={"MERGE"})
     * @ORM\JoinColumn(referencedColumnName="country")
     */
    public $country;
}
