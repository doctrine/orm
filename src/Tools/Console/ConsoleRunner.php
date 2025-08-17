<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console;

use Composer\InstalledVersions;
use Doctrine\DBAL\Tools\Console as DBALConsole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\ConnectionFromManagerProvider;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\HelperSetManagerProvider;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use OutOfBoundsException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\HelperSet;

use function assert;
use function class_exists;

/**
 * Handles running the Console Tools inside Symfony Console context.
 */
final class ConsoleRunner
{
    use ApplicationCompatibility;

    /**
     * Create a Symfony Console HelperSet
     *
     * @deprecated This method will be removed in ORM 3.0 without replacement.
     */
    public static function createHelperSet(EntityManagerInterface $entityManager): HelperSet
    {
        $helpers = ['em' => new EntityManagerHelper($entityManager)];

        if (class_exists(DBALConsole\Helper\ConnectionHelper::class)) {
            $helpers['db'] = new DBALConsole\Helper\ConnectionHelper($entityManager->getConnection());
        }

        return new HelperSet($helpers);
    }

    /**
     * Runs console with the given helper set.
     *
     * @param HelperSet|EntityManagerProvider $helperSetOrProvider
     * @param SymfonyCommand[]                $commands
     */
    public static function run($helperSetOrProvider, array $commands = []): void
    {
        $cli = self::createApplication($helperSetOrProvider, $commands);
        $cli->run();
    }

    /**
     * Creates a console application with the given helperset and
     * optional commands.
     *
     * @param HelperSet|EntityManagerProvider $helperSetOrProvider
     * @param SymfonyCommand[]                $commands
     *
     * @throws OutOfBoundsException
     */
    public static function createApplication($helperSetOrProvider, array $commands = []): Application
    {
        $version = InstalledVersions::getVersion('doctrine/orm');
        assert($version !== null);

        $cli = new Application('Doctrine Command Line Interface', $version);
        $cli->setCatchExceptions(true);

        if ($helperSetOrProvider instanceof HelperSet) {
            $cli->setHelperSet($helperSetOrProvider);

            // @phpstan-ignore new.deprecatedClass, method.deprecatedClass
            $helperSetOrProvider = new HelperSetManagerProvider($helperSetOrProvider);
        }

        self::addCommands($cli, $helperSetOrProvider);
        $cli->addCommands($commands);

        return $cli;
    }

    public static function addCommands(Application $cli, ?EntityManagerProvider $entityManagerProvider = null): void
    {
        if ($entityManagerProvider === null) {
            // @phpstan-ignore new.deprecatedClass, method.deprecatedClass
            $entityManagerProvider = new HelperSetManagerProvider($cli->getHelperSet());
        }

        $connectionProvider = new ConnectionFromManagerProvider($entityManagerProvider);

        if (class_exists(DBALConsole\Command\ImportCommand::class)) {
            self::addCommandToApplication($cli, new DBALConsole\Command\ImportCommand());
        }

        $cli->addCommands(
            [
                // DBAL Commands
                // @phpstan-ignore new.deprecatedClass, method.deprecatedClass
                new DBALConsole\Command\ReservedWordsCommand($connectionProvider),
                new DBALConsole\Command\RunSqlCommand($connectionProvider),

                // ORM Commands
                new Command\ClearCache\CollectionRegionCommand($entityManagerProvider),
                new Command\ClearCache\EntityRegionCommand($entityManagerProvider),
                new Command\ClearCache\MetadataCommand($entityManagerProvider),
                new Command\ClearCache\QueryCommand($entityManagerProvider),
                new Command\ClearCache\QueryRegionCommand($entityManagerProvider),
                new Command\ClearCache\ResultCommand($entityManagerProvider),
                new Command\SchemaTool\CreateCommand($entityManagerProvider),
                new Command\SchemaTool\UpdateCommand($entityManagerProvider),
                new Command\SchemaTool\DropCommand($entityManagerProvider),
                // @phpstan-ignore new.deprecatedClass
                new Command\EnsureProductionSettingsCommand($entityManagerProvider),
                // @phpstan-ignore new.deprecatedClass
                new Command\ConvertDoctrine1SchemaCommand(),
                // @phpstan-ignore new.deprecatedClass
                new Command\GenerateRepositoriesCommand($entityManagerProvider),
                // @phpstan-ignore new.deprecatedClass
                new Command\GenerateEntitiesCommand($entityManagerProvider),
                new Command\GenerateProxiesCommand($entityManagerProvider),
                // @phpstan-ignore new.deprecatedClass
                new Command\ConvertMappingCommand($entityManagerProvider),
                new Command\RunDqlCommand($entityManagerProvider),
                new Command\ValidateSchemaCommand($entityManagerProvider),
                new Command\InfoCommand($entityManagerProvider),
                new Command\MappingDescribeCommand($entityManagerProvider),
            ]
        );
    }

    /** @deprecated This method will be removed in ORM 3.0 without replacement. */
    public static function printCliConfigTemplate(): void
    {
        echo <<<'HELP'
You are missing a "cli-config.php" or "config/cli-config.php" file in your
project, which is required to get the Doctrine Console working. You can use the
following sample as a template:

<?php
use Doctrine\ORM\Tools\Console\ConsoleRunner;

// replace with file to your own project bootstrap
require_once 'bootstrap.php';

// replace with mechanism to retrieve EntityManager in your app
$entityManager = GetEntityManager();

return ConsoleRunner::createHelperSet($entityManager);

HELP;
    }
}
