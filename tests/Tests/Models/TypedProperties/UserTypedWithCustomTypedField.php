<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\DbalTypes\CustomIdObject;

#[ORM\Entity]
#[ORM\Table(name: 'cms_users_typed_with_custom_typed_field')]
class UserTypedWithCustomTypedField
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column]
    public CustomIdObject $customId;

    #[ORM\Column]
    public int $customIntTypedField;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
        $metadata->setPrimaryTable(
            ['name' => 'cms_users_typed_with_custom_typed_field'],
        );

        $metadata->mapField(
            [
                'id' => true,
                'fieldName' => 'id',
            ],
        );
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        $metadata->mapField(
            ['fieldName' => 'customId'],
        );

        $metadata->mapField(
            ['fieldName' => 'customIntTypedField'],
        );
    }
}
