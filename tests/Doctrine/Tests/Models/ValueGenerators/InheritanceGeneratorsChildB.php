<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueGenerators;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class InheritanceGeneratorsChildB extends InheritanceGeneratorsChildA
{
    /**
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue("CUSTOM")
     * @ORM\CustomIdGenerator("Doctrine\Tests\Models\ValueGenerators\BarGenerator")
     * @var string|null
     */
    private $b;

    public function getB() : ?string
    {
        return $this->b;
    }
}
