<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\ValueGenerators;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("vg_non_identifier_generators")
 */
class NonIdentifierGenerators
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue("AUTO")
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue("CUSTOM")
     * @ORM\CustomIdGenerator("Doctrine\Tests\Models\ValueGenerators\FooGenerator")
     * @var string|null
     */
    private $foo;

    /**
     * @ORM\Column(type="string")
     * @ORM\GeneratedValue("CUSTOM")
     * @ORM\CustomIdGenerator("Doctrine\Tests\Models\ValueGenerators\BarGenerator")
     * @var string|null
     */
    private $bar;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getFoo() : ?string
    {
        return $this->foo;
    }

    public function getBar() : ?string
    {
        return $this->bar;
    }
}
