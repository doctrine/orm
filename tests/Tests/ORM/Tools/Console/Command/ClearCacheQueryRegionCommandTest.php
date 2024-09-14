<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/** @group DDC-2183 */
class ClearCacheQueryRegionCommandTest extends OrmFunctionalTestCase
{
    /** @var Application */
    private $application;

    /** @var QueryRegionCommand */
    private $command;

    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->command = new QueryRegionCommand(new SingleManagerProvider($this->_em));

        $this->application = new Application();
        $this->application->add($this->command);
    }

    public function testClearAllRegion(): void
    {
        $command = $this->application->find('orm:clear-cache:region:query');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command' => $command->getName(),
                '--all'   => true,
            ],
            ['decorated' => false]
        );

        self::assertStringContainsString(' // Clearing all second-level cache query regions', $tester->getDisplay());
    }

    public function testClearDefaultRegionName(): void
    {
        $command = $this->application->find('orm:clear-cache:region:query');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command'     => $command->getName(),
                'region-name' => null,
            ],
            ['decorated' => false]
        );

        self::assertStringContainsString(
            ' // Clearing second-level cache query region named "query_cache_region"',
            $tester->getDisplay()
        );
    }

    public function testClearByRegionName(): void
    {
        $command = $this->application->find('orm:clear-cache:region:query');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command'     => $command->getName(),
                'region-name' => 'my_region',
            ],
            ['decorated' => false]
        );

        self::assertStringContainsString(
            ' // Clearing second-level cache query region named "my_region"',
            $tester->getDisplay()
        );
    }

    public function testFlushRegionName(): void
    {
        $command = $this->application->find('orm:clear-cache:region:query');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command'     => $command->getName(),
                'region-name' => 'my_region',
                '--flush'     => true,
            ],
            ['decorated' => false]
        );

        self::assertStringContainsString(
            ' // Flushing cache provider configured for second-level cache query region named "my_region"',
            $tester->getDisplay()
        );
    }
}
