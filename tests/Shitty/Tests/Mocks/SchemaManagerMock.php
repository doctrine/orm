<?php

namespace Shitty\Tests\Mocks;

/**
 * Mock class for AbstractSchemaManager.
 */
class SchemaManagerMock extends \Shitty\DBAL\Schema\AbstractSchemaManager
{
    /**
     * @param \Shitty\DBAL\Connection $conn
     */
    public function __construct(\Shitty\DBAL\Connection $conn)
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
