<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;

/**
 * @Entity
 */
class DDC964Organization
{
    /**
     * @var int
     * @GeneratedValue
     * @Id
     * @Column(type="integer")
     */
    private $id;

    /**
     * @var string|null
     * @Column
     */
    private $name;

    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
