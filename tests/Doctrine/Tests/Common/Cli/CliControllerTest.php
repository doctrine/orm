<?php

namespace Doctrine\Tests\Common\Cli;

use Doctrine\Tests\Mocks\TaskMock;
use Doctrine\Common\Cli\Configuration;
use Doctrine\Common\Cli\CliController;

require_once __DIR__ . '/../../TestInit.php';

/**
 * @author Nils Adermann <naderman@naderman.de>
 */
class CliControllerTest extends \Doctrine\Tests\DoctrineTestCase
{
    private $cli;

    /**
     * Sets up a CliController instance with a task referencing the TaskMock
     * class. Instances of that class created by the CliController can be
     * inspected for correctness.
     */
    function setUp()
    {
        $config = $this->getMock('\Doctrine\Common\Cli\Configuration');
        $printer = $this->getMockForAbstractClass('\Doctrine\Common\Cli\Printers\AbstractPrinter');

        $this->cli = new CliController($config, $printer);

        TaskMock::$instances = array();

        $this->cli->addTask('task-mock', '\Doctrine\Tests\Mocks\TaskMock');
    }

    /**
     * Data provider with a bunch of task-mock calls with different arguments
     * and their expected parsed format.
     */
    static public function dataCliControllerArguments()
    {
        return array(
            array(
                array('doctrine', 'Core:task-mock', '--bool'),
                array('bool' => true),
                'Bool option'
            ),
            array(
                array('doctrine', 'Core:task-mock', '--option=value'),
                array('option' => 'value'),
                'Option with string value'
            ),
            array(
                array('doctrine', 'Core:task-mock', '--option=value, with additional, info'),
                array('option' => 'value, with additional, info'),
                'Option with string value containing space and comma'
            ),
            array(
                array('doctrine', 'Core:task-mock', '--option='),
                array('option' => array()),
                'Empty option value'
            ),
            array(
                array('doctrine', 'Core:task-mock', '--option=value1,value2,value3'),
                array('option' => array('value1', 'value2', 'value3')),
                'Option with list of string values'
            ),
        );
    }

    /**
     * Checks whether the arguments coming from the data provider are correctly
     * parsed by the CliController and passed to the task to be run.
     *
     * @dataProvider dataCliControllerArguments
     * @param array $rawArgs
     * @param array $parsedArgs
     * @param string $message
     */
    public function testArgumentParsing($rawArgs, $parsedArgs, $message)
    {
        $this->cli->run($rawArgs);

        $this->assertEquals(count(TaskMock::$instances), 1);

        $task = TaskMock::$instances[0];

        $this->assertEquals($task->getArguments(), $parsedArgs, $message);
    }

    /**
     * Checks whether multiple tasks in one command are correctly run with
     * their respective options.
     */
    public function testMultipleTaskExecution()
    {
        $this->cli->run(array(
            'doctrine',
            'Core:task-mock',
            '--option=',
            'Core:task-mock',
            '--bool'
        ));

        $this->assertEquals(count(TaskMock::$instances), 2);

        $task0 = TaskMock::$instances[0];
        $task1 = TaskMock::$instances[1];

        $this->assertEquals($task0->getRunCounter(), 1);
        $this->assertEquals($task1->getRunCounter(), 1);

        $this->assertEquals($task0->getArguments(), array('option' => array()));
        $this->assertEquals($task1->getArguments(), array('bool' => true));
    }
}
