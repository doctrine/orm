<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\SchemaTool;

use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\Tests\ORM\Tools\Console\Command\SchemaTool\Models\Keyboard;

final class DropCommandTest extends CommandTestCase
{
    /** @doesNotPerformAssertions */
    public function testItPrintsTheSql(): void
    {
        $this->createSchemaForModels(Keyboard::class);
        $tester = $this->getCommandTester(DropCommand::class);
        $tester->execute(['--dump-sql' => true]);

        self::$sharedConn->executeStatement($tester->getDisplay());
    }
}
