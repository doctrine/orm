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
}
