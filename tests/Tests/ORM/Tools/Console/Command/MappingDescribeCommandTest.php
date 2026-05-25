<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\ApplicationCompatibility;
use Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\Tests\Models\Cache\AttractionInfo;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for {@see \Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand}
 *
 * @covers \Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand
 */
class MappingDescribeCommandTest extends OrmFunctionalTestCase
{
    use ApplicationCompatibility;

    /** @var Application */
    private $application;

    /** @var MappingDescribeCommand */
    private $command;

    /** @var CommandTester */
    private $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = new Application();
        self::addCommandToApplication($this->application, new MappingDescribeCommand(new SingleManagerProvider($this->_em)));

        $this->command = $this->application->find('orm:mapping:describe');
        $this->tester  = new CommandTester($this->command);
    }

    public function testShowSpecificFuzzySingle(): void
    {
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'AttractionInfo',
            ]
        );

        $display = $this->tester->getDisplay();

        self::assertStringContainsString(AttractionInfo::class, $display);
        self::assertStringContainsString('Root entity name', $display);
    }

    public function testShowSpecificFuzzyAmbiguous(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('possible matches');
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'Attraction',
            ]
        );
    }

    public function testShowSpecificNotFound(): void
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Could not find any mapped Entity classes matching "AttractionFooBar"');
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'AttractionFooBar',
            ]
        );
    }

    public function testShowEntityWithEntityListeners(): void
    {
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => CmsAddress::class,
            ]
        );

        $display = $this->tester->getDisplay();

        self::assertStringContainsString(CmsAddress::class, $display);
        self::assertStringContainsString('Entity listeners', $display);
        self::assertStringContainsString('CmsAddressListener', $display);
        self::assertStringContainsString('postPersist', $display);
        self::assertStringContainsString('prePersist', $display);
    }
}
