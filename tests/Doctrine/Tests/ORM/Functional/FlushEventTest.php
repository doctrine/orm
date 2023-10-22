<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

use function get_class;

/**
 * FlushEventTest
 */
class FlushEventTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();
    }

    public function testPersistNewEntitiesOnPreFlush(): void
    {
        $this->_em->getEventManager()->addEventListener(Events::onFlush, new OnFlushListener());

        $user           = new CmsUser();
        $user->username = 'romanb';
        $user->name     = 'Roman';
        $user->status   = 'Dev';

        $this->_em->persist($user);

        self::assertEquals(0, $user->phonenumbers->count());

        $this->_em->flush();

        self::assertEquals(1, $user->phonenumbers->count());
        self::assertTrue($this->_em->contains($user->phonenumbers->get(0)));
        self::assertSame($user->phonenumbers->get(0)->getUser(), $user);

        self::assertFalse($user->phonenumbers->isDirty());

        // Can be used together with SQL Logging to check that a subsequent flush has
        // nothing to do. This proofs the correctness of the changes that happened in onFlush.
        //echo "SECOND FLUSH";
        //$this->_em->flush();
    }

    /** @group DDC-2173 */
    public function testPreAndOnFlushCalledAlways(): void
    {
        $listener = new OnFlushCalledListener();
        $this->_em->getEventManager()->addEventListener(Events::onFlush, $listener);
        $this->_em->getEventManager()->addEventListener(Events::preFlush, $listener);
        $this->_em->getEventManager()->addEventListener(Events::postFlush, $listener);

        $this->_em->flush();

        self::assertEquals(1, $listener->preFlush);
        self::assertEquals(1, $listener->onFlush);

        $this->_em->flush();

        self::assertEquals(2, $listener->preFlush);
        self::assertEquals(2, $listener->onFlush);
    }
}

class OnFlushListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        //echo "---preFlush".PHP_EOL;

        $em  = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof CmsUser) {
                // Adds a phonenumber to every newly persisted CmsUser ...

                $phone              = new CmsPhonenumber();
                $phone->phonenumber = 12345;
                // Update object model
                $entity->addPhonenumber($phone);
                // Invoke regular persist call
                $em->persist($phone);
                // Explicitly calculate the changeset since onFlush is raised
                // after changeset calculation!
                $uow->computeChangeSet($em->getClassMetadata(get_class($phone)), $phone);

                // Take a snapshot because the UoW wont do this for us, because
                // the UoW did not visit this collection.
                // Alternatively we could provide an ->addVisitedCollection() method
                // on the UoW.
                $entity->getPhonenumbers()->takeSnapshot();
            }

            /*foreach ($uow->getEntityChangeSet($entity) as $field => $change) {
                list ($old, $new) = $change;

                var_dump($old);
            }*/
        }
    }
}

class OnFlushCalledListener
{
    /** @var int */
    public $preFlush = 0;

    /** @var int */
    public $onFlush = 0;

    /** @var int */
    public $postFlush = 0;

    public function preFlush($args): void
    {
        $this->preFlush++;
    }

    public function onFlush($args): void
    {
        $this->onFlush++;
    }

    public function postFlush($args): void
    {
        $this->postFlush++;
    }
}
