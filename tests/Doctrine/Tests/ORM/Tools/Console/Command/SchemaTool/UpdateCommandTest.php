<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\SchemaTool;

use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;

use function method_exists;

class UpdateCommandTest extends AbstractCommandTest
{
    /** @doesNotPerformAssertions */
    public function testItPrintsTheSql(): void
    {
        if (! method_exists(SchemaDiff::class, 'toSaveSql')) {
            self::markTestSkipped('FIXME for DBAL 4.');
        }

        $tester = $this->getCommandTester(UpdateCommand::class);
        $tester->execute(
            ['--dump-sql' => true],
            ['capture_stderr_separately' => true],
        );

        self::$sharedConn->executeStatement($tester->getDisplay());
    }
}
