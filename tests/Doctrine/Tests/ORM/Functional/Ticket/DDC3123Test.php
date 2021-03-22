<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Events;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use ReflectionObject;
use stdClass;

/**
 * @group DDC-3123
 */
class DDC3123Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testIssue(): void
    {
        $test = $this;
        $user = new CmsUser();
        $uow  = $this->_em->getUnitOfWork();

        $user->name     = 'Marco';
        $user->username = 'ocramius';

        $this->_em->persist($user);
        $uow->scheduleExtraUpdate($user, ['name' => 'changed name']);

        $listener = $this->getMockBuilder(stdClass::class)
                         ->setMethods([Events::postFlush])
                         ->getMock();

        $listener
            ->expects($this->once())
            ->method(Events::postFlush)
            ->will($this->returnCallback(static function () use ($uow, $test): void {
                $reflection = new ReflectionObject($uow);
                $property   = $reflection->getProperty('extraUpdates');

                $property->setAccessible(true);
                $test->assertEmpty(
                    $property->getValue($uow),
                    'ExtraUpdates are reset before postFlush'
                );
            }));

        $this->_em->getEventManager()->addEventListener(Events::postFlush, $listener);

        $this->_em->flush();
    }
}
