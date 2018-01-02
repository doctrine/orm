<?php

declare(strict_types=1);

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
     * @ORM\ManyToOne(targetEntity=Country::class, cascade={"MERGE"})
     * @ORM\JoinColumn(referencedColumnName="country")
     */
    public $country;
}
