<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\SchemaTool;

use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;

class UpdateCommandTest extends CommandTestCase
{
    /** @doesNotPerformAssertions */
    public function testItPrintsTheSql(): void
    {
        $tester = $this->getCommandTester(UpdateCommand::class);
        $tester->execute(
            ['--dump-sql' => true],
            ['capture_stderr_separately' => true]
        );

        self::$sharedConn->executeStatement($tester->getDisplay());
    }

    public function testCheckSyncExitCode(): void
    {
        $tester = $this->getCommandTester(UpdateCommand::class);
        $tester->execute(
            ['--check-sync' => true],
            ['capture_stderr_separately' => true]
        );

        self::assertStringContainsString(
            '[ERROR] The mapping metadata is not in sync with the current database schema',
            $tester->getErrorOutput()
        );

        self::assertSame(1, $tester->getStatusCode());
    }
}
