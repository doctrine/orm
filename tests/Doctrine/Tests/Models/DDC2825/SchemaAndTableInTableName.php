<?php

namespace Doctrine\Tests\Models\DDC2825;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;

/**
 * Quoted column name to check that sequence names are
 * correctly handled
 *
 * @Entity
 * @Table(name="implicit_table", schema="implicit_schema")
 */
class SchemaAndTableInTableName
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $tableMetadata = new Mapping\TableMetadata();

        $tableMetadata->setName('implicit_table');
        $tableMetadata->setSchema('implicit_schema');

        $metadata->setPrimaryTable($tableMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('id');

        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setPrimaryKey(true);

        $metadata->addProperty($fieldMetadata);
    }
}
