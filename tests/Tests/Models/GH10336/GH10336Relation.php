<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH10336;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="gh10336_relations")
 */
class GH10336Relation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="string")
     */
    public string $value;
}
