<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\Tests\Models\Cache\AttractionInfo;
use Doctrine\Tests\OrmFunctionalTestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for {@see \Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand}
 */
#[CoversClass(MappingDescribeCommand::class)]
class MappingDescribeCommandTest extends OrmFunctionalTestCase
{
    private Application $application;

    private MappingDescribeCommand $command;

    private CommandTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = new Application();
        $this->application->add(new MappingDescribeCommand(new SingleManagerProvider($this->_em)));

        $this->command = $this->application->find('orm:mapping:describe');
        $this->tester  = new CommandTester($this->command);
    }

    public function testShowSpecificFuzzySingle(): void
    {
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'AttractionInfo',
            ],
        );

        $display = $this->tester->getDisplay();

        self::assertStringContainsString(AttractionInfo::class, $display);
        self::assertStringContainsString('Root entity name', $display);
    }

    public function testShowSpecificFuzzyAmbiguous(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('possible matches');
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'Attraction',
            ],
        );
    }

    public function testShowSpecificNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not find any mapped Entity classes matching "AttractionFooBar"');
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'AttractionFooBar',
            ],
        );
    }
}
