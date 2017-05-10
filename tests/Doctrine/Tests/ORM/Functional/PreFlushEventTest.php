<?php
/**
 * @package Doctrine\Tests\ORM\Functional
 * @since 25.12.2013
 * @author eshenbrener
 */

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

require_once __DIR__ . '/../../TestInit.php';

/**
 *
 * @package Doctrine\Tests\ORM\Functional
 * @author eshenbrener
 */
class PreFlushEventTest extends OrmFunctionalTestCase
{

    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }


    public function testThatPreFlushEventCalledOnlyOnceInEventManager()
    {
        $this->_em->getClassMetadata('Doctrine\Tests\Models\CMS\CmsUser')->entityListeners[Events::preFlush][] = array(
            'class' => 'Doctrine\Tests\ORM\Functional\UserListener',
            'method' => 'preFlush'
        );
        /** @var \Doctrine\Tests\ORM\Functional\UserListener $entityListener */
        $entityListener = $this->_em->getConfiguration()->getEntityListenerResolver()->resolve('Doctrine\Tests\ORM\Functional\UserListener');

        $listener = $this->getMock('Doctrine\Tests\ORM\Functional\PreFlushListener');
        $listener->expects($this->exactly(1))
            ->method('preFlush');
        $this->_em->getEventManager()->addEventListener(array(Events::preFlush), $listener);

        $user1 = new CmsUser();
        $user1->name = $user1->username = uniqid();
        $user2 = new CmsUser();
        $user2->name = $user2->username = uniqid();

        $this->_em->persist( $user1 );
        $this->_em->persist( $user2 );
        $this->_em->flush();

        $this->assertEquals(2, $entityListener->getCallCount());
    }
}

class PreFlushListener
{
    public function preFlush()
    {

    }
}

class UserListener
{
    protected $callCount = 0;

    public function preFlush(CmsUser $user, PreFlushEventArgs $event)
    {
        $this->callCount++;
    }

    public function getCallCount()
    {
        return $this->callCount;
    }
}