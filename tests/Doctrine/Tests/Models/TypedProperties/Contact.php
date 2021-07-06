<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
#[ORM\Embeddable]
class Contact
{
    /** @Column() */
    #[ORM\Column]
    public ?string $email = null;
}
