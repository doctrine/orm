<?php

$em = require_once __DIR__.'/bootstrap.php';

$cli = new \Symfony\Component\Console\Application('Doctrine Command Line Interface', Shitty\Common\Version::VERSION);
$cli->setCatchExceptions(true);

$cli->setHelperSet(new Symfony\Component\Console\Helper\HelperSet(array(
    'db' => new \Shitty\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Shitty\ORM\Tools\Console\Helper\EntityManagerHelper($em)
)));

$cli->addCommands(array(
    // DBAL Commands
    new \Shitty\DBAL\Tools\Console\Command\RunSqlCommand(),
    new \Shitty\DBAL\Tools\Console\Command\ImportCommand(),

    // ORM Commands
    new \Shitty\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand(),
    new \Shitty\ORM\Tools\Console\Command\ClearCache\EntityRegionCommand(),
    new \Shitty\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand(),
    new \Shitty\ORM\Tools\Console\Command\ClearCache\MetadataCommand(),
    new \Shitty\ORM\Tools\Console\Command\ClearCache\ResultCommand(),
    new \Shitty\ORM\Tools\Console\Command\ClearCache\QueryCommand(),
    new \Shitty\ORM\Tools\Console\Command\SchemaTool\CreateCommand(),
    new \Shitty\ORM\Tools\Console\Command\SchemaTool\UpdateCommand(),
    new \Shitty\ORM\Tools\Console\Command\SchemaTool\DropCommand(),
    new \Shitty\ORM\Tools\Console\Command\EnsureProductionSettingsCommand(),
    new \Shitty\ORM\Tools\Console\Command\ConvertDoctrine1SchemaCommand(),
    new \Shitty\ORM\Tools\Console\Command\GenerateRepositoriesCommand(),
    new \Shitty\ORM\Tools\Console\Command\GenerateEntitiesCommand(),
    new \Shitty\ORM\Tools\Console\Command\GenerateProxiesCommand(),
    new \Shitty\ORM\Tools\Console\Command\ConvertMappingCommand(),
    new \Shitty\ORM\Tools\Console\Command\RunDqlCommand(),
    new \Shitty\ORM\Tools\Console\Command\ValidateSchemaCommand(),

));
$cli->run();
