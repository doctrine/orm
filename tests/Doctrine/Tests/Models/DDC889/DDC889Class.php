<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC889;

use Doctrine\ORM\Annotation as ORM;

class DDC889Class extends DDC889SuperClass
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;
}
