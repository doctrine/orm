<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Product;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
#[ORM\Entity]
class Product
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column()
     * @ORM\GeneratedValue
     */
    #[ORM\Id]
    #[ORM\Column()]
    #[ORM\GeneratedValue]
    private $id = 42;

    /**
     * @var string
     * @ORM\Column()
     */
    #[ORM\Column()]
    private $name;

    /**
     * @var string|null
     */
    private $image = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): void
    {
        $this->image = $image;
    }
}
