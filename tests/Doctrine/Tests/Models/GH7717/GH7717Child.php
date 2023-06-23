<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH7717;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="gh7717_children")
 */
class GH7717Child
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    public ?string $nullableProperty = null;
}
