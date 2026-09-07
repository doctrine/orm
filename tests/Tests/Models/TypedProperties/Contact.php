<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;

#[ORM\Embeddable]
class Contact
{
    #[ORM\Column]
    public string|null $email = null;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField(['fieldName' => 'email', 'type' => 'string']);
    }
}
