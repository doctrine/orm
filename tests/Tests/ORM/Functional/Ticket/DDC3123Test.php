<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use ReflectionProperty;

#[Group('DDC-3123')]
class DDC3123Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testIssue(): void
    {
        $user = new CmsUser();
        $uow  = $this->_em->getUnitOfWork();

        $user->name     = 'Marco';
        $user->username = 'ocramius';

        $this->_em->persist($user);
        $uow->scheduleExtraUpdate($user, ['name' => 'changed name']);

        $this->_em->getEventManager()->addEventListener(Events::postFlush, new class ($uow) {
            /** @var UnitOfWork */
            private $uow;

            public function __construct(UnitOfWork $uow)
            {
                $this->uow = $uow;
            }

            public function postFlush(): void
            {
                $property = new ReflectionProperty(UnitOfWork::class, 'extraUpdates');

                Assert::assertEmpty(
                    $property->getValue($this->uow),
                    'ExtraUpdates are reset before postFlush',
                );
            }
        });

        $this->_em->flush();
    }
}
