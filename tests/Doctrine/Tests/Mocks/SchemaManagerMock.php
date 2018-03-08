<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * Mock class for AbstractSchemaManager.
 */
class SchemaManagerMock extends AbstractSchemaManager
{
    public function __construct(Connection $conn)
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
