<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC5934;

use Doctrine\ORM\Mapping\AssociationOverride;
use Doctrine\ORM\Mapping\AssociationOverrides;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
#[AssociationOverrides([new AssociationOverride(name: 'members', fetch: 'EXTRA_LAZY')])]
class DDC5934Contract extends DDC5934BaseContract
{
    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setAssociationOverride('members', [
            'fetch' => ClassMetadata::FETCH_EXTRA_LAZY,
        ]);
    }
}
