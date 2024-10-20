<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function method_exists;

/**
 * Tests for {@see \Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand}
 */
#[CoversClass(ValidateSchemaCommand::class)]
class ValidateSchemaCommandTest extends OrmFunctionalTestCase
{
    private ValidateSchemaCommand $command;

    private CommandTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->_em->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            self::markTestSkipped('Only with sqlite');
        }

        if (! method_exists(SchemaDiff::class, 'toSaveSql')) {
            self::markTestSkipped('FIXME for DBAL 4.');
        }

        $application = new Application();
        $application->add(new ValidateSchemaCommand(new SingleManagerProvider($this->_em)));

        $this->command = $application->find('orm:validate-schema');
        $this->tester  = new CommandTester($this->command);
    }

    public function testNotInSync(): void
    {
        $this->tester->execute(
            [
                'command' => $this->command->getName(),
            ],
        );

        $display = $this->tester->getDisplay();

        self::assertStringContainsString('The database schema is not in sync with the current mapping file', $display);
        self::assertStringNotContainsString('cache_login', $display);
    }

    public function testNotInSyncVerbose(): void
    {
        $schemaManager = $this->createSchemaManager();
        if ($schemaManager->tablesExist(['cache_login'])) {
            $schemaManager->dropTable('cache_login');
        }

        $this->tester->execute(
            [
                'command' => $this->command->getName(),
            ],
            [
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
            ],
        );

        $display = $this->tester->getDisplay();
        self::assertStringContainsString('The database schema is not in sync with the current mapping file', $display);
        self::assertStringContainsString('cache_login', $display);
    }
}
