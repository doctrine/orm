<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * @group DDC-3146
 * @author Emiel Nijpels <emiel@silverstreet.com>
 */
class DDC3146Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * Verify that the number of added events to the event listener from the abstract hydrator class is equal to the number of removed events
     */
    public function testEventListeners()
    {
        // Create mock connection to be returned from the entity manager interface
        $mockConnection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $mockEntityManagerInterface = $this->getMockBuilder('Doctrine\ORM\EntityManagerInterface')->disableOriginalConstructor()->getMock();
        $mockEntityManagerInterface->expects($this->any())->method('getConnection')->will($this->returnValue($mockConnection));

        // Create mock event manager to be returned from the entity manager interface
        $mockEventManager = $this->getMockBuilder('Doctrine\Common\EventManager')->disableOriginalConstructor()->getMock();
        $mockEntityManagerInterface->expects($this->any())->method('getEventManager')->will($this->returnValue($mockEventManager));

        // Create mock statement and result mapping
        $mockStatement = $this->getMockBuilder('Doctrine\DBAL\Driver\Statement')->disableOriginalConstructor()->getMock();
        $mockStatement->expects($this->once())->method('fetch')->will($this->returnValue(false));
        $mockResultMapping = $this->getMockBuilder('Doctrine\ORM\Query\ResultSetMapping')->disableOriginalConstructor()->getMock();

        // Create mock abstract hydrator
        $mockAbstractHydrator = $this->getMockBuilder('Doctrine\ORM\Internal\Hydration\AbstractHydrator')
            ->setConstructorArgs(array($mockEntityManagerInterface))
            ->setMethods(array('hydrateAllData'))
            ->getMock();

        // Increase counter every time the event listener is added and decrease the counter every time the event listener is removed
        $eventCounter = 0;
        $mockEventManager->expects($this->any())
            ->method('addEventListener')
            ->will(
                $this->returnCallback(
                    function () use (&$eventCounter) {
                        $eventCounter++;
                    }
                )
            );

        $mockEventManager->expects($this->any())
            ->method('removeEventListener')
            ->will(
                $this->returnCallback(
                    function () use (&$eventCounter) {
                        $eventCounter--;
                    }
                )
            );

        // Create iterable result
        $iterableResult = $mockAbstractHydrator->iterate($mockStatement, $mockResultMapping, array());
        $iterableResult->next();

        // Number of added events listeners should be equal or less than the number of removed events
        $this->assertLessThanOrEqual(0, $eventCounter, 'More events added to the event listener than removed; this can create a memory leak when references are not cleaned up');
    }
}
