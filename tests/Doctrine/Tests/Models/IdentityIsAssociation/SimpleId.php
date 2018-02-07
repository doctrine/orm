<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\IdentityIsAssociation;

use Doctrine\ORM\Annotation as ORM;

/** @ORM\Entity */
class SimpleId
{
    /** @ORM\Id @ORM\Column(name="id", type="integer") */
    public $id;
}
