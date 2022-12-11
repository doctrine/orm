<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\DbalTypes\CustomIdObject;

/**
 * @Entity
 * @Table(name="cms_users_typed_with_custom_typed_field")
 */
#[ORM\Entity]
#[ORM\Table(name: 'cms_users_typed_with_custom_typed_field')]
class UserTypedWithCustomTypedField
{
    /**
     * @Id
     * @Column
     * @GeneratedValue
     */
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int $id;

    /** @Column */
    #[ORM\Column]
    public CustomIdObject $customId;

    /** @Column */
    #[ORM\Column]
    public int $customIntTypedField;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
        $metadata->setPrimaryTable(
            ['name' => 'cms_users_typed_with_custom_typed_field']
        );

        $metadata->mapField(
            [
                'id' => true,
                'fieldName' => 'id',
            ]
        );
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        $metadata->mapField(
            ['fieldName' => 'customId']
        );

        $metadata->mapField(
            ['fieldName' => 'customIntTypedField']
        );
    }
}
