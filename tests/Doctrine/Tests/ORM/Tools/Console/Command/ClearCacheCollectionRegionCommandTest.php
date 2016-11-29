<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Application;
use Doctrine\Tests\OrmFunctionalTestCase;

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

        $this->application = new Application();
        $this->command     = new CollectionRegionCommand();

        $this->application->setHelperSet(new HelperSet(array(
            'em' => new EntityManagerHelper($this->_em)
        )));

        $this->application->add($this->command);
    }

    public function testClearAllRegion()
    {
        $command    = $this->application->find('orm:clear-cache:region:collection');
        $tester     = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            '--all'   => true,
        ), array('decorated' => false));

        $this->assertEquals('Clearing all second-level cache collection regions' . PHP_EOL, $tester->getDisplay());
    }

    public function testClearByOwnerEntityClassName()
    {
        $command    = $this->application->find('orm:clear-cache:region:collection');
        $tester     = new CommandTester($command);
        $tester->execute(array(
            'command'       => $command->getName(),
            'owner-class'   => 'Doctrine\Tests\Models\Cache\State',
            'association'   => 'cities',
        ), array('decorated' => false));

        $this->assertEquals('Clearing second-level cache for collection "Doctrine\Tests\Models\Cache\State#cities"' . PHP_EOL, $tester->getDisplay());
    }

    public function testClearCacheEntryName()
    {
        $command    = $this->application->find('orm:clear-cache:region:collection');
        $tester     = new CommandTester($command);
        $tester->execute(array(
            'command'       => $command->getName(),
            'owner-class'   => 'Doctrine\Tests\Models\Cache\State',
            'association'   => 'cities',
            'owner-id'      => 1,
        ), array('decorated' => false));

        $this->assertEquals('Clearing second-level cache entry for collection "Doctrine\Tests\Models\Cache\State#cities" owner entity identified by "1"' . PHP_EOL, $tester->getDisplay());
    }

    public function testFlushRegionName()
    {
        $command    = $this->application->find('orm:clear-cache:region:collection');
        $tester     = new CommandTester($command);
        $tester->execute(array(
            'command'       => $command->getName(),
            'owner-class'   => 'Doctrine\Tests\Models\Cache\State',
            'association'   => 'cities',
            '--flush'       => true,
        ), array('decorated' => false));

        $this->assertEquals('Flushing cache provider configured for "Doctrine\Tests\Models\Cache\State#cities"' . PHP_EOL, $tester->getDisplay());
    }
}
