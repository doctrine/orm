<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\ClearCache\EntityRegionCommand;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Doctrine\Tests\Models\Cache\Country;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

use function preg_replace;
use function trim;

#[Group('DDC-2183')]
class ClearCacheEntityRegionCommandTest extends OrmFunctionalTestCase
{
    private Application $application;

    private EntityRegionCommand $command;

    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->command = new EntityRegionCommand(new SingleManagerProvider($this->_em));

        $this->application = new Application();
        $this->application->add($this->command);
    }

    public function testClearAllRegion(): void
    {
        $command = $this->application->find('orm:clear-cache:region:entity');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command' => $command->getName(),
                '--all'   => true,
            ],
            ['decorated' => false],
        );

        self::assertStringContainsString(' // Clearing all second-level cache entity regions', $tester->getDisplay());
    }

    public function testClearByEntityClassName(): void
    {
        $command = $this->application->find('orm:clear-cache:region:entity');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command'      => $command->getName(),
                'entity-class' => Country::class,
            ],
            ['decorated' => false],
        );

        self::assertStringContainsString(
            ' // Clearing second-level cache for entity "Doctrine\Tests\Models\Cache\Country"',
            $tester->getDisplay(),
        );
    }

    public function testClearCacheEntryName(): void
    {
        $command = $this->application->find('orm:clear-cache:region:entity');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command'      => $command->getName(),
                'entity-class' => Country::class,
                'entity-id'    => 1,
            ],
            ['decorated' => false],
        );

        self::assertSame(
            'Clearing second-level cache entry for entity "Doctrine\Tests\Models\Cache\Country" identified by "1"',
            trim(preg_replace('#\s+//\s#', ' ', $tester->getDisplay())),
        );
    }

    public function testFlushRegionName(): void
    {
        $command = $this->application->find('orm:clear-cache:region:entity');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command'      => $command->getName(),
                'entity-class' => Country::class,
                '--flush'      => true,
            ],
            ['decorated' => false],
        );

        self::assertStringContainsString(
            ' // Flushing cache provider configured for entity named "Doctrine\Tests\Models\Cache\Country"',
            $tester->getDisplay(),
        );
    }
}
