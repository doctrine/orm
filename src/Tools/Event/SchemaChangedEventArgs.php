<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Event;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;

class SchemaChangedEventArgs extends EventArgs
{
    /** @param array<string> $sqls */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Schema $schema,
        private readonly Schema $oldSchema,
        private readonly array $sqls,
    ) {
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getOldSchema(): Schema
    {
        return $this->oldSchema;
    }

    /** @return array<string> */
    public function getSqls(): array
    {
        return $this->sqls;
    }
}
