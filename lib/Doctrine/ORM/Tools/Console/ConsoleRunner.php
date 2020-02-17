<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Console;

use Doctrine\DBAL\Tools\Console as DBALConsole;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use OutOfBoundsException;
use PackageVersions\Versions;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\HelperSet;

/**
 * Handles running the Console Tools inside Symfony Console context.
 */
final class ConsoleRunner
{
    /**
     * Create a Symfony Console HelperSet
     */
    public static function createHelperSet(EntityManagerInterface $entityManager) : HelperSet
    {
        return new HelperSet(
            [
                'db' => new DBALConsole\Helper\ConnectionHelper($entityManager->getConnection()),
                'em' => new EntityManagerHelper($entityManager),
            ]
        );
    }

    /**
     * Runs console with the given helper set.
     *
     * @param SymfonyCommand[] $commands
     */
    public static function run(HelperSet $helperSet, array $commands = []) : void
    {
        $cli = self::createApplication($helperSet, $commands);
        $cli->run();
    }

    /**
     * Creates a console application with the given helperset and
     * optional commands.
     *
     * @param SymfonyCommand[] $commands
     *
     * @throws OutOfBoundsException
     */
    public static function createApplication(HelperSet $helperSet, array $commands = []) : Application
    {
        $cli = new Application('Doctrine Command Line Interface', Versions::getVersion('doctrine/orm'));
        $cli->setCatchExceptions(true);
        $cli->setHelperSet($helperSet);
        self::addCommands($cli);
        $cli->addCommands($commands);

        return $cli;
    }

    public static function addCommands(Application $cli) : void
    {
        $cli->addCommands(
            [
                // DBAL Commands
                new DBALConsole\Command\ReservedWordsCommand(),
                new DBALConsole\Command\RunSqlCommand(),

                // ORM Commands
                new Command\ClearCache\CollectionRegionCommand(),
                new Command\ClearCache\EntityRegionCommand(),
                new Command\ClearCache\MetadataCommand(),
                new Command\ClearCache\QueryCommand(),
                new Command\ClearCache\QueryRegionCommand(),
                new Command\ClearCache\ResultCommand(),
                new Command\SchemaTool\CreateCommand(),
                new Command\SchemaTool\UpdateCommand(),
                new Command\SchemaTool\DropCommand(),
                new Command\EnsureProductionSettingsCommand(),
                new Command\GenerateProxiesCommand(),
                new Command\RunDqlCommand(),
                new Command\ValidateSchemaCommand(),
                new Command\InfoCommand(),
                new Command\MappingDescribeCommand(),
            ]
        );
    }

    public static function printCliConfigTemplate() : void
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
