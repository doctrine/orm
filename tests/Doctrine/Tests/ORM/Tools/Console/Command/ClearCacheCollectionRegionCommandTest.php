<?php

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
    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

    /**
     * @var \Doctrine\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand
     */
    private $command;

    protected function setUp()
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->command = new CollectionRegionCommand();

        $this->application = new Application();
        $this->application->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($this->_em)]));
        $this->application->add($this->command);
    }

    public function testClearAllRegion()
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

        self::assertContains(' // Clearing all second-level cache collection regions', $tester->getDisplay());
    }

    public function testClearByOwnerEntityClassName()
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

        self::assertContains(
            ' // Clearing second-level cache for collection "Doctrine\Tests\Models\Cache\State#cities"',
            $tester->getDisplay()
        );
    }

    public function testClearCacheEntryName()
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

        self::assertContains(
            ' // Clearing second-level cache entry for collection "Doctrine\Tests\Models\Cache\State#cities" owner',
            $tester->getDisplay()
        );

        self::assertContains(' // entity identified by "1"', $tester->getDisplay());
    }

    public function testFlushRegionName()
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

        self::assertContains(
            ' // Flushing cache provider configured for "Doctrine\Tests\Models\Cache\State#cities"',
            $tester->getDisplay()
        );
    }
}
