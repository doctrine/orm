<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\SchemaTool;

use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;

use function method_exists;

class UpdateCommandTest extends CommandTestCase
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

    /** @dataProvider getCasesForWarningMessageFromCompleteOption */
    public function testWarningMessageFromCompleteOption(string|null $name, string $expectedMessage): void
    {
        $tester = $this->getCommandTester(UpdateCommand::class, $name);
        $tester->execute(
            [],
            ['capture_stderr_separately' => true],
        );

        self::assertStringContainsString($expectedMessage, $tester->getErrorOutput());
    }

    public static function getCasesForWarningMessageFromCompleteOption(): iterable
    {
        if (! method_exists(SchemaDiff::class, 'toSaveSql')) {
            self::markTestSkipped('This test requires DBAL 3');
        }

        yield 'default_name' => [
            null,
            '[WARNING] Not passing the "--complete" option to "orm:schema-tool:update" is deprecated',
        ];

        yield 'custom_name' => [
            'doctrine:schema:update',
            '[WARNING] Not passing the "--complete" option to "doctrine:schema:update" is deprecated',
        ];
    }
}
