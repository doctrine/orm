<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Contact
{
    #[ORM\Column]
    public string|null $email = null;
}
