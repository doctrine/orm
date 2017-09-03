<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueGenerators;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class InheritanceGeneratorsChildA extends InheritanceGeneratorsRoot
{
    /**
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue("CUSTOM")
     * @ORM\CustomIdGenerator("Doctrine\Tests\Models\ValueGenerators\FooGenerator")
     * @var string|null
     */
    private $a;

    public function getA() : ?string
    {
        return $this->a;
    }
}
