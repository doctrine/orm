<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Console\Command;

use Doctrine\ORM\Tools\Console\Command\InfoCommand;
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
    /** @var Application */
    private $application;

    /** @var InfoCommand */
    private $command;

    /** @var CommandTester */
    private $tester;

    protected function setUp() : void
    {
        parent::setUp();

        $this->application = new Application();
        $this->application->setHelperSet(new HelperSet(['em' => new EntityManagerHelper($this->em)]));
        $this->application->add(new MappingDescribeCommand());

        $this->command = $this->application->find('orm:mapping:describe');
        $this->tester  = new CommandTester($this->command);
    }

    public function testShowSpecificFuzzySingle() : void
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage possible matches
     */
    public function testShowSpecificFuzzyAmbiguous() : void
    {
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'Attraction',
            ]
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Could not find any mapped Entity classes matching "AttractionFooBar"
     */
    public function testShowSpecificNotFound() : void
    {
        $this->tester->execute(
            [
                'command'    => $this->command->getName(),
                'entityName' => 'AttractionFooBar',
            ]
        );
    }
}
