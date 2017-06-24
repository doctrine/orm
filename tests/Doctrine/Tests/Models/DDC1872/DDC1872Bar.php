<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC1872;

use Doctrine\ORM\Annotation as ORM;

/**
* @ORM\Entity
*/
class DDC1872Bar
{
    /** @ORM\Id @ORM\Column(type="string") */
    private $id;
}
