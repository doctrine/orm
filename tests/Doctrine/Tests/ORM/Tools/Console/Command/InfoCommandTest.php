<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\Tests\Models\Cache\AttractionInfo;
use Doctrine\Tests\Models\Cache\City;
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

        $this->application->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($this->_em)]));
        $this->application->add(new InfoCommand());

        $this->command = $this->application->find('orm:info');
        $this->tester  = new CommandTester($this->command);
    }

    public function testListAllClasses()
    {
        $this->tester->execute(['command' => $this->command->getName()]);

        self::assertContains(AttractionInfo::class, $this->tester->getDisplay());
        self::assertContains(City::class, $this->tester->getDisplay());
    }

    public function testEmptyEntityClassNames() :  void
    {
        $mappingDriver = $this->createMock(MappingDriver::class);
        $configuration = $this->createMock(Configuration::class);
        $em            = $this->createMock(EntityManagerInterface::class);

        $mappingDriver->method('getAllClassNames')
                      ->willReturn([]);

        $configuration->method('getMetadataDriverImpl')
                      ->willReturn($mappingDriver);

        $em->method('getConfiguration')
           ->willReturn($configuration);

        $application = new Application();
        $application->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($em)]));
        $application->add(new InfoCommand());

        $command = $application->find('orm:info');
        $tester  = new CommandTester($command);

        $tester->execute(['command' => $command->getName()]);

        self::assertContains(
            ' ! [CAUTION] You do not have any mapped Doctrine ORM entities according to the current configuration',
            $tester->getDisplay()
        );

        self::assertContains(
            ' !           If you have entities or mapping files you should check your mapping configuration for errors.',
            $tester->getDisplay()
        );
    }

    public function testInvalidEntityClassMetadata() : void
    {
        $mappingDriver = $this->createMock(MappingDriver::class);
        $configuration = $this->createMock(Configuration::class);
        $em            = $this->createMock(EntityManagerInterface::class);

        $mappingDriver->method('getAllClassNames')
                      ->willReturn(['InvalidEntity']);

        $configuration->method('getMetadataDriverImpl')
                      ->willReturn($mappingDriver);

        $em->method('getConfiguration')
           ->willReturn($configuration);

        $em->method('getClassMetadata')
           ->with('InvalidEntity')
           ->willThrowException(new MappingException('exception message'));

        $application = new Application();
        $application->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($em)]));
        $application->add(new InfoCommand());

        $command = $application->find('orm:info');
        $tester  = new CommandTester($command);

        $tester->execute(['command' => $command->getName()]);

        self::assertContains('[FAIL] InvalidEntity', $tester->getDisplay());
        self::assertContains('exception message', $tester->getDisplay());
    }
}
