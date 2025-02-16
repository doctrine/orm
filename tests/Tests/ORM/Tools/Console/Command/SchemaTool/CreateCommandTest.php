<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\SchemaTool;

use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

class CreateCommandTest extends CommandTestCase
{
    #[DoesNotPerformAssertions]
    public function testItPrintsTheSql(): void
    {
        $tester = $this->getCommandTester(CreateCommand::class);
        $tester->execute(['--dump-sql' => true]);

        self::$sharedConn->executeStatement($tester->getDisplay());
    }
}
