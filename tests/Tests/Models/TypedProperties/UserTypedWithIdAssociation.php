<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\CMS\CmsEmail;

#[ORM\Entity]
#[ORM\Table(name: 'cms_users_typed_id_association')]
class UserTypedWithIdAssociation
{
    /** Nullable in PHP, but identifier join columns are always NOT NULL. */
    #[ORM\Id]
    #[ORM\ManyToOne]
    public CmsEmail|null $nullableEmail = null;

    #[ORM\Id]
    #[ORM\ManyToOne]
    public CmsEmail $nonNullableEmail;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapManyToOne(
            [
                'fieldName' => 'nullableEmail',
                'id' => true,
            ],
        );

        $metadata->mapManyToOne(
            [
                'fieldName' => 'nonNullableEmail',
                'id' => true,
            ],
        );
    }
}
