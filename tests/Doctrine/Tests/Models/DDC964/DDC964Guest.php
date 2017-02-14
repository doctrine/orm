<?php

namespace Doctrine\Tests\Models\DDC964;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * @ORM\Entity
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(
 *          name="id",
 *          column=@ORM\Column(
 *              name = "guest_id",
 *              type = "integer"
 *          )
 *      ),
 *      @ORM\AttributeOverride(
 *          name="name",
 *          column=@ORM\Column(
 *              name     = "guest_name",
 *              type     = "string",
 *              nullable = false,
 *              unique   = true,
                length   = 240
 *          )
 *      )
 * })
 */
class DDC964Guest extends DDC964User
{
    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $fieldMetadata = new Mapping\FieldMetadata('id');

        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setColumnName('guest_id');
        $fieldMetadata->setPrimaryKey(true);

        $metadata->setAttributeOverride($fieldMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('name');

        $fieldMetadata->setType(Type::getType('string'));
        $fieldMetadata->setLength(240);
        $fieldMetadata->setColumnName('guest_name');
        $fieldMetadata->setNullable(false);
        $fieldMetadata->setUnique(true);

        $metadata->setAttributeOverride($fieldMetadata);
    }
}
