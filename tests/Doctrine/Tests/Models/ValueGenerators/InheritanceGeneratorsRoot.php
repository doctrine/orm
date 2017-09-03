<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueGenerators;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("vg_inheritance_generators")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="integer")
 * @ORM\DiscriminatorMap({
 *     1 = "Doctrine\Tests\Models\ValueGenerators\InheritanceGeneratorsRoot",
 *     2 = "Doctrine\Tests\Models\ValueGenerators\InheritanceGeneratorsChildA",
 *     3 = "Doctrine\Tests\Models\ValueGenerators\InheritanceGeneratorsChildB"
 * })
 */
class InheritanceGeneratorsRoot
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @var int|null
     */
    private $id;

    public function getId() : ?int
    {
        return $this->id;
    }
}
