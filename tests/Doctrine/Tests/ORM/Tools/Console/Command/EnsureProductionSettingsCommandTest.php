<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\Tests\DoctrineTestCase;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

use function array_merge;

class EnsureProductionSettingsCommandTest extends DoctrineTestCase
{
    public function testExecute(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('ensureProductionSettings');

        $em->method('getConfiguration')
            ->willReturn($configuration);

        $em->expects($this->never())
            ->method('getConnection');

        $this->assertSame(0, $this->executeCommand($em));
    }

    public function testExecuteFailed(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('ensureProductionSettings')
            ->willThrowException(new RuntimeException());

        $em->method('getConfiguration')
            ->willReturn($configuration);

        $em->expects($this->never())
            ->method('getConnection');

        $this->assertSame(1, $this->executeCommand($em));
    }

    public function testExecuteWithComplete(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('ensureProductionSettings');

        $em->method('getConfiguration')
            ->willReturn($configuration);

        $connection = $this->createMock(Connection::class);

        $connection->expects($this->once())
            ->method('connect');

        $em->method('getConnection')
            ->willReturn($connection);

        $this->assertSame(0, $this->executeCommand($em, ['--complete' => true]));
    }

    public function testExecuteWithCompleteFailed(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('ensureProductionSettings');

        $em->method('getConfiguration')
            ->willReturn($configuration);

        $connection = $this->createMock(Connection::class);

        $connection->expects($this->once())
            ->method('connect')
            ->willThrowException(new RuntimeException());

        $em->method('getConnection')
            ->willReturn($connection);

        $this->assertSame(1, $this->executeCommand($em, ['--complete' => true]));
    }

    /**
     * @param mixed[] $options
     */
    private function executeCommand(
        EntityManagerInterface $em,
        array $input = []
    ): int {
        $application = new Application();
        $application->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($em)]));
        $application->add(new EnsureProductionSettingsCommand());

        $command = $application->find('orm:ensure-production-settings');
        $tester  = new CommandTester($command);

        return $tester->execute(
            array_merge([
                'command'   => $command->getName(),
            ], $input)
        );
    }
}
