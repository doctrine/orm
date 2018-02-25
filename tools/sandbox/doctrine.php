<?php


$em = require_once __DIR__ . '/bootstrap.php';
use Doctrine\DBAL\Tools\Console\Command\ImportCommand;
use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\EntityRegionCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\MetadataCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand;
use Doctrine\ORM\Tools\Console\Command\ClearCache\ResultCommand;
use Doctrine\ORM\Tools\Console\Command\ConvertMappingCommand;
use Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateEntitiesCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateProxiesCommand;
use Doctrine\ORM\Tools\Console\Command\GenerateRepositoriesCommand;
use Doctrine\ORM\Tools\Console\Command\RunDqlCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\DropCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\UpdateCommand;
use Doctrine\ORM\Tools\Console\Command\ValidateSchemaCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Application;

$cli = new Application('Doctrine Command Line Interface', Doctrine\Common\Version::VERSION);
$cli->setCatchExceptions(true);

$cli->setHelperSet(new Symfony\Component\Console\Helper\HelperSet(
    [
        'db' => new ConnectionHelper($em->getConnection()),
        'em' => new EntityManagerHelper($em),
    ]
));

$cli->addCommands(
    [
    // DBAL Commands
    new RunSqlCommand(),
    new ImportCommand(),

    // ORM Commands
    new QueryRegionCommand(),
    new EntityRegionCommand(),
    new CollectionRegionCommand(),
    new MetadataCommand(),
    new ResultCommand(),
    new QueryCommand(),
    new CreateCommand(),
    new UpdateCommand(),
    new DropCommand(),
    new EnsureProductionSettingsCommand(),
    new GenerateRepositoriesCommand(),
    new GenerateEntitiesCommand(),
    new GenerateProxiesCommand(),
    new ConvertMappingCommand(),
    new RunDqlCommand(),
    new ValidateSchemaCommand(),

    ]
);
$cli->run();
