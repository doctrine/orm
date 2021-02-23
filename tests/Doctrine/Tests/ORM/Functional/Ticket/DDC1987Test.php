<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query;

/**
 * @group DDC-2494
 */
class DDC1987Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    private $gearman = null;
    private $results = array();

    protected function setUp()
    {
        if (!class_exists('GearmanClient', false)) {
            $this->markTestSkipped('pecl/gearman is required for this test to run.');
        }

        $workers = shell_exec('(echo workers ; sleep 0.1) | netcat 127.0.0.1 4730');
        if (substr_count($workers, 'incrementPrice') < 2) {
            $this->markTestSkipped('At least two gearman workers are required for this test to run.');
        }

        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(\Doctrine\Tests\Models\DDC1987\DDC1987Order::CLASSNAME),
            $this->_em->getClassMetadata(\Doctrine\Tests\Models\DDC1987\DDC1987Item::CLASSNAME),
        ));

        $this->tasks = array();

        $this->gearman = new \GearmanClient();
        $this->gearman->addServer();
        $this->gearman->setCompleteCallback(array($this, "gearmanTaskCompleted"));

        $order = new \Doctrine\Tests\Models\DDC1987\DDC1987Order(1);
        $item = new \Doctrine\Tests\Models\DDC1987\DDC1987Item($order, 1);
        $order->getItems()->add($item);

        $this->_em->persist($order);
        $this->_em->flush();
    }

    public function gearmanTaskCompleted($task)
    {
        $this->results[] = $task->data();

        if (count($this->results) == 2) {
            $this->assertTrue($this->results[1] == '3');
        }
    }

    public function testIssue()
    {
        $this->gearman->addTask('incrementPrice', serialize(array(
            'conn' => $this->_em->getConnection()->getParams(),
            'delay' => 0
        )));

        $this->gearman->addTask('incrementPrice', serialize(array(
            'conn' => $this->_em->getConnection()->getParams(),
            'delay' => 1
        )));

        $this->gearman->runTasks();
    }
}



