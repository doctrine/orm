<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Application;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-2183
 */
class ClearCacheQueryRegionCommandTest extends OrmFunctionalTestCase
{
    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

    /**
     * @var \Doctrine\ORM\Tools\Console\Command\ClearCache\QueryRegionCommand
     */
    private $command;

    protected function setUp()
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->application = new Application();
        $this->command     = new QueryRegionCommand();

        $this->application->setHelperSet(new HelperSet(array(
            'em' => new EntityManagerHelper($this->_em)
        )));

        $this->application->add($this->command);
    }

    public function testClearAllRegion()
    {
        $command    = $this->application->find('orm:clear-cache:region:query');
        $tester     = new CommandTester($command);
        $tester->execute(array(
            'command' => $command->getName(),
            '--all'   => true,
        ), array('decorated' => false));

        $this->assertEquals('Clearing all second-level cache query regions' . PHP_EOL, $tester->getDisplay());
    }

    public function testClearDefaultRegionName()
    {
        $command    = $this->application->find('orm:clear-cache:region:query');
        $tester     = new CommandTester($command);
        $tester->execute(array(
            'command'       => $command->getName(),
            'region-name'   => null,
        ), array('decorated' => false));

        $this->assertEquals('Clearing second-level cache query region named "query_cache_region"' . PHP_EOL, $tester->getDisplay());
    }

    public function testClearByRegionName()
    {
        $command    = $this->application->find('orm:clear-cache:region:query');
        $tester     = new CommandTester($command);
        $tester->execute(array(
            'command'       => $command->getName(),
            'region-name'   => 'my_region',
        ), array('decorated' => false));

        $this->assertEquals('Clearing second-level cache query region named "my_region"' . PHP_EOL, $tester->getDisplay());
    }

    public function testFlushRegionName()
    {
        $command    = $this->application->find('orm:clear-cache:region:query');
        $tester     = new CommandTester($command);
        $tester->execute(array(
            'command'       => $command->getName(),
            'region-name'   => 'my_region',
            '--flush'       => true,
        ), array('decorated' => false));

        $this->assertEquals('Flushing cache provider configured for second-level cache query region named "my_region"' . PHP_EOL, $tester->getDisplay());
    }
}
