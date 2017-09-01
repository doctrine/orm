<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueGenerators;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("vg_composite_generated_identifier")
 */
class CompositeGeneratedIdentifier
{
    /**
     * @ORM\Column
     * @ORM\Id
     * @ORM\GeneratedValue("CUSTOM")
     * @ORM\CustomIdGenerator("Doctrine\Tests\Models\ValueGenerators\FooGenerator")
     * @var string|null
     */
    private $a;

    /**
     * @ORM\Column
     * @ORM\Id
     * @ORM\GeneratedValue("CUSTOM")
     * @ORM\CustomIdGenerator("Doctrine\Tests\Models\ValueGenerators\BarGenerator")
     * @var string|null
     */
    private $b;

    public function getA() : ?string
    {
        return $this->a;
    }

    public function getB() : ?string
    {
        return $this->b;
    }
}
