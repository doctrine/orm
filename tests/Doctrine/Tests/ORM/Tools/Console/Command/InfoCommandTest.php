<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\ClearCache\CollectionRegionCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Application;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\ORM\Tools\Console\Command\InfoCommand;

class InfoCommandTest extends OrmFunctionalTestCase
{
    /**
     * @var \Symfony\Component\Console\Application
     */
    private $application;

    /**
     * @var \Doctrine\ORM\Tools\Console\Command\InfoCommand
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
        $command           = new InfoCommand();

        $this->application->setHelperSet(new HelperSet(array(
            'em' => new EntityManagerHelper($this->_em)
        )));

        $this->application->add($command);

        $this->command    = $this->application->find('orm:info');
        $this->tester     = new CommandTester($command);
    }

    public function testListAllClasses()
    {
        $this->tester->execute(array(
            'command' => $this->command->getName(),
        ));

        $this->assertContains('Doctrine\Tests\Models\Cache\AttractionInfo', $this->tester->getDisplay());
        $this->assertContains('Doctrine\Tests\Models\Cache\City', $this->tester->getDisplay());
    }
}
