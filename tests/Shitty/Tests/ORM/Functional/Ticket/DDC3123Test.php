<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\ORM\Events;
use Shitty\Tests\Models\CMS\CmsUser;

/**
 * @group DDC-3123
 */
class DDC3123Test extends \Shitty\Tests\OrmFunctionalTestCase
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

        $listener = $this->getMock('stdClass', array(Events::postFlush));

        $listener
            ->expects($this->once())
            ->method(Events::postFlush)
            ->will($this->returnCallback(function () use ($uow, $test) {
                $test->assertAttributeEmpty('extraUpdates', $uow, 'ExtraUpdates are reset before postFlush');
            }));

        $this->_em->getEventManager()->addEventListener(Events::postFlush, $listener);

        $this->_em->flush();
    }
}
