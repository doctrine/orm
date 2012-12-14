<?php

namespace Doctrine\Tests\Mocks;

/**
 * Mock class for AbstractSchemaManager.
 */
class SchemaManagerMock extends \Doctrine\DBAL\Schema\AbstractSchemaManager
{
    /**
     * @param \Doctrine\DBAL\Connection $conn
     */
    public function __construct(\Doctrine\DBAL\Connection $conn)
    {
        parent::__construct($conn);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
    }
}
