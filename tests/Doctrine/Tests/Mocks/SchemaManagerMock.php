<?php

declare(strict_types=1);

namespace Doctrine\Tests\Mocks;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Exception;

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
        throw new Exception('not implemented');
    }
}
