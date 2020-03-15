<?php

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\MappingDescribeCommand;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Doctrine\Tests\Models\Cache\AttractionInfo;
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
        $this->application->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($this->_em)]));
        $this->application->add(new MappingDescribeCommand());

        $this->command = $this->application->find('orm:mapping:describe');
        $this->tester  = new CommandTester($this->command);
    }

    public function testShowSpecificFuzzySingle()
    {
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'AttractionInfo',
            ]
        );

        $display = $this->tester->getDisplay();

        self::assertContains(AttractionInfo::class, $display);
        self::assertContains('Root entity name', $display);
    }

    public function testShowSpecificFuzzyAmbiguous()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('possible matches');
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'Attraction',
            ]
        );
    }

    public function testShowSpecificNotFound()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Could not find any mapped Entity classes matching "AttractionFooBar"');
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'AttractionFooBar',
            ]
        );
    }
}

