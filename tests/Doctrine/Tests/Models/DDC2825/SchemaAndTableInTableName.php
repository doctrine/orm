<?php

namespace Doctrine\Tests\Models\DDC2825;

use Doctrine\DBAL\Types\Type;

/**
 * Quoted column name to check that sequence names are
 * correctly handled
 *
 * @Entity @Table(name="implicit_schema.implicit_table")
 */
class SchemaAndTableInTableName
{
    /** @Id @Column(type="integer") @GeneratedValue(strategy="AUTO") */
    public $id;

    public static function loadMetadata(\Doctrine\ORM\Mapping\ClassMetadata $metadata)
    {
        $metadata->setPrimaryTable(array(
            'name' => 'implicit_schema.implicit_table',
        ));

        $metadata->addProperty('id', Type::getType('integer'), array (
            'id' => true,
        ));
    }
}
