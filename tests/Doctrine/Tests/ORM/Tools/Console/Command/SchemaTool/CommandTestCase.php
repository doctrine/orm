<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\SchemaTool;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\AbstractCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends OrmFunctionalTestCase
{
    /** @param class-string<AbstractCommand> $commandClass */
    protected function getCommandTester(string $commandClass, string|null $commandName = null): CommandTester
    {
        $entityManager = $this->getEntityManager(null, new AttributeDriver([
            __DIR__ . '/Models',
        ]));

        if (! $entityManager->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            self::markTestSkipped('We are testing the symfony/console integration');
        }

        $command = new $commandClass(
            new SingleManagerProvider($entityManager),
        );

        if ($commandName !== null) {
            $command->setName($commandName);
        }

        return new CommandTester($command);
    }
}
