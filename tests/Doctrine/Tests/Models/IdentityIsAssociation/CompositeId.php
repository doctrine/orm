<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\IdentityIsAssociation;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class CompositeId
{
    /** @ORM\Id @ORM\Column(name="id_a", type="integer") */
    public $idA;

    /** @ORM\Id @ORM\Column(name="id_b", type="integer") */
    public $idB;
}
