<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Event;

use Doctrine\Common\EventArgs;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Event Args used for the Events::postGenerateSchema event.
 *
 * @link        www.doctrine-project.com
 */
class GenerateSchemaEventArgs extends EventArgs
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Schema $schema,
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
}
