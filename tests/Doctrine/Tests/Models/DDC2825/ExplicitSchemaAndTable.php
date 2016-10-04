<?php

namespace Doctrine\Tests\Models\DDC2825;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;

/** @Entity @Table(name="explicit_table", schema="explicit_schema") */
class ExplicitSchemaAndTable
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $tableMetadata = new Mapping\TableMetadata();

        $tableMetadata->setSchema('explicit_schema');
        $tableMetadata->setName('explicit_table');

        $metadata->setPrimaryTable($tableMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('id');

        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setPrimaryKey(true);

        $metadata->addProperty($fieldMetadata);
    }
}
