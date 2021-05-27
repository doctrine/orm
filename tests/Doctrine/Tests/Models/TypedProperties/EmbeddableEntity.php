<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable
 */
#[ORM\Embeddable]
class EmbeddableEntity
{
    /**
     * @ORM\Id
     * @ORM\Column
     * @ORM\GeneratedValue
     */
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    public int $id;
}
