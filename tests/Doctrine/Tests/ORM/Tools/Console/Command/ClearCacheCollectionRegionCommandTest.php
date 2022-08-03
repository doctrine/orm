<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\Tests\Models\Cache\State;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group DDC-2183
 */
class ClearCacheCollectionRegionCommandTest extends OrmFunctionalTestCase
{
    /** @var Application */
    private $application;

    /** @var CollectionRegionCommand */
    private $command;

    protected function setUp(): void
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->command = new CollectionRegionCommand();

        $this->application = new Application();
        $this->application->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($this->_em)]));
        $this->application->add($this->command);
    }

    public function testClearAllRegion(): void
    {
        $command = $this->application->find('orm:clear-cache:region:collection');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command' => $command->getName(),
                '--all'   => true,
            ],
            ['decorated' => false]
        );

        self::assertStringContainsString(' // Clearing all second-level cache collection regions', $tester->getDisplay());
    }

    public function testClearByOwnerEntityClassName(): void
    {
        $command = $this->application->find('orm:clear-cache:region:collection');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command'     => $command->getName(),
                'owner-class' => State::class,
                'association' => 'cities',
            ],
            ['decorated' => false]
        );

        self::assertStringContainsString(
            ' // Clearing second-level cache for collection "Doctrine\Tests\Models\Cache\State#cities"',
            $tester->getDisplay()
        );
    }

    public function testClearCacheEntryName(): void
    {
        $command = $this->application->find('orm:clear-cache:region:collection');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command'     => $command->getName(),
                'owner-class' => State::class,
                'association' => 'cities',
                'owner-id'    => 1,
            ],
            ['decorated' => false]
        );

        self::assertStringContainsString(
            ' // Clearing second-level cache entry for collection "Doctrine\Tests\Models\Cache\State#cities" owner',
            $tester->getDisplay()
        );

        self::assertStringContainsString('identified by "1"', $tester->getDisplay());
    }

    public function testFlushRegionName(): void
    {
        $command = $this->application->find('orm:clear-cache:region:collection');
        $tester  = new CommandTester($command);

        $tester->execute(
            [
                'command'     => $command->getName(),
                'owner-class' => State::class,
                'association' => 'cities',
                '--flush'     => true,
            ],
            ['decorated' => false]
        );

        self::assertStringContainsString(
            ' // Flushing cache provider configured for "Doctrine\Tests\Models\Cache\State#cities"',
            $tester->getDisplay()
        );
    }
}
