<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Application;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand;

class EnsureProductionSettingsCommandTest extends OrmFunctionalTestCase
{
    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

    /**
     * @var \Doctrine\ORM\Tools\Console\Command\EnsureProductionSettingsCommand
     */
    private $command;

    /**
     * @var \Symfony\Component\Console\Tester\CommandTester
     */
    private $tester;

    protected function setUp()
    {
        parent::setUp();

        $this->application = new Application();
        $command           = new EnsureProductionSettingsCommand();

        $this->application->setHelperSet(new HelperSet(array(
            'em' => new EntityManagerHelper($this->_em)
        )));

        $this->application->add($command);

        $this->command = $this->application->find('orm:ensure-production-settings');
        $this->tester  = new CommandTester($command);
        $this->enableProductionSettings();
    }

    public function testWillNotCheckConnection()
    {
        $this->tester->execute(array(
            'command' => $this->command->getName(),
            '--complete' => false
        ));

        $this->assertContains('Environment is correctly configured for production.', $this->tester->getDisplay());
    }

    public function testWillCheckConnection()
    {
        $this->tester->execute(array(
            'command' => $this->command->getName(),
            '--complete' => true
        ));

        $this->assertContains('Environment is correctly configured for production.', $this->tester->getDisplay());
    }

    private function enableProductionSettings()
    {
        $config = $this->_em->getConfiguration();
        $config->setMetadataCacheImpl(new MemcachedCache);
        $config->setQueryCacheImpl(new MemcachedCache);
        $config->setResultCacheImpl(new MemcachedCache);
        $config->setAutoGenerateProxyClasses(false);
    }
}
