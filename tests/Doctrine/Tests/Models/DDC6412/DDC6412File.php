<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC6412;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class DDC6412File
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
    
    /**
     * @ORM\Column(length=50, name="file_name")
     */
    public $name;
}

