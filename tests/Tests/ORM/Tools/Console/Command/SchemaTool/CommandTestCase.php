<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command\SchemaTool;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\AbstractCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\Tests\Mocks\AttributeDriverFactory;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Console\Tester\CommandTester;

abstract class CommandTestCase extends OrmFunctionalTestCase
{
    /** @param class-string<AbstractCommand> $commandClass */
    protected function getCommandTester(string $commandClass, string|null $commandName = null): CommandTester
    {
        $attributeDriver = AttributeDriverFactory::createAttributeDriver([__DIR__ . '/Models']);
        $entityManager   = $this->getEntityManager(null, $attributeDriver);

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
