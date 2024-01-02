<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Event;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Event Args used for the Events::postGenerateSchemaTable event.
 *
 * @link        www.doctrine-project.com
 */
class GenerateSchemaTableEventArgs extends EventArgs
{
    public function __construct(
        private readonly ClassMetadata $classMetadata,
        private readonly Schema $schema,
        private readonly Table $classTable,
    ) {
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getClassTable(): Table
    {
        return $this->classTable;
    }
}
