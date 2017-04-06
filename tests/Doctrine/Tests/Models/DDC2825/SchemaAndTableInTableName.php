<?php

namespace Doctrine\Tests\Models\DDC2825;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Mapping;

/**
 * Quoted column name to check that sequence names are
 * correctly handled
 *
 * @ORM\Entity
 * @ORM\Table(name="implicit_table", schema="implicit_schema")
 */
class SchemaAndTableInTableName
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    public static function loadMetadata(Mapping\ClassMetadata $metadata)
    {
        $tableMetadata = new Mapping\TableMetadata();

        $tableMetadata->setName('implicit_table');
        $tableMetadata->setSchema('implicit_schema');

        $metadata->setTable($tableMetadata);

        $fieldMetadata = new Mapping\FieldMetadata('id');

        $fieldMetadata->setType(Type::getType('integer'));
        $fieldMetadata->setPrimaryKey(true);

        $metadata->addProperty($fieldMetadata);
    }
}
