<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Rating;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="ratings_user")
 */
class User
{
    /**
     * @Id
     * @Column(type="string")
     */
    private string $id;
    /**
     * @Column(type="string", length=255, nullable=false)
     */
    private string $name;

    public function __construct(string $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
