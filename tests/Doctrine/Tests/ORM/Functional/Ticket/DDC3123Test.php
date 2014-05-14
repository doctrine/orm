<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Events;
use Doctrine\Tests\Models\CMS\CmsUser;

/**
 * @group DDC-3123
 */
class DDC3123Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testIssue()
    {
        $test = $this;
        $user = new CmsUser();
        $uow  = $this->_em->getUnitOfWork();

        $user->name     = 'Marco';
        $user->username = 'ocramius';

        $this->_em->persist($user);
        $uow->scheduleExtraUpdate($user, array('name' => 'changed name'));

        $listener = $this->getMock('stdClass', array('postFlush'));

        $listener
            ->expects($this->once())
            ->method('postFlush')
            ->will($this->returnCallback(function () use ($uow, $test) {
                $extraUpdatesReflection = new \ReflectionProperty($uow, 'extraUpdates');

                $extraUpdatesReflection->setAccessible(true);

                $test->assertEmpty($extraUpdatesReflection->getValue($uow));
            }));

        $this->_em->getEventManager()->addEventListener(Events::postFlush, $listener);

        $this->_em->flush();
    }
}
