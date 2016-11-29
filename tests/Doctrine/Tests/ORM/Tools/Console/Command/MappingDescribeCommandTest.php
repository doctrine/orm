<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\Tests\OrmFunctionalTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests for {@see \Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand}
 *
 * @covers \Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand
 */
class MappingDescribeCommandTest extends OrmFunctionalTestCase
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
        $command = new MappingDescribeCommand();

        $this->application->setHelperSet(new HelperSet(array(
            'em' => new EntityManagerHelper($this->_em)
        )));

        $this->application->add($command);

        $this->command = $this->application->find('orm:mapping:describe');
        $this->tester = new CommandTester($command);
    }

    public function testShowSpecificFuzzySingle()
    {
        $this->tester->execute(array(
            'command' => $this->command->getName(),
            'entityName' => 'AttractionInfo',
        ));

        $display = $this->tester->getDisplay();
        $this->assertContains('Doctrine\Tests\Models\Cache\AttractionInfo', $display);
        $this->assertContains('Root entity name', $display);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage possible matches
     */
    public function testShowSpecificFuzzyAmbiguous()
    {
        $this->tester->execute(array(
            'command' => $this->command->getName(),
            'entityName' => 'Attraction',
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Could not find any mapped Entity classes matching "AttractionFooBar"
     */
    public function testShowSpecificNotFound()
    {
        $this->tester->execute(array(
            'command' => $this->command->getName(),
            'entityName' => 'AttractionFooBar'
        ));
    }
}

