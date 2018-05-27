<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Utility\StaticClassNameConverter;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * PostLoadEventTest
 */
class PostLoadEventTest extends OrmFunctionalTestCase
{
    /** @var int */
    private $userId;

    /**
     * {@inheritdoc}
     */
    protected function setUp() : void
    {
        $this->useModelSet('cms');

        parent::setUp();

        $this->loadFixture();
    }

    public function testLoadedEntityUsingFindShouldTriggerEvent() : void
    {
        $mockListener = $this->createMock(PostLoadListener::class);

        // CmsUser and CmsAddres, because it's a ToOne inverse side on CmsUser
        $mockListener
            ->expects($this->exactly(2))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->em->getEventManager();

        $eventManager->addEventListener([Events::postLoad], $mockListener);

        $this->em->find(CmsUser::class, $this->userId);
    }

    public function testLoadedEntityUsingQueryShouldTriggerEvent() : void
    {
        $mockListener = $this->createMock(PostLoadListener::class);

        // CmsUser and CmsAddres, because it's a ToOne inverse side on CmsUser
        $mockListener
            ->expects($this->exactly(2))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->em->getEventManager();

        $eventManager->addEventListener([Events::postLoad], $mockListener);

        $query = $this->em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id');

        $query->setParameter('id', $this->userId);
        $query->getResult();
    }

    public function testLoadedAssociationToOneShouldTriggerEvent() : void
    {
        $mockListener = $this->createMock(PostLoadListener::class);

        // CmsUser (root), CmsAddress (ToOne inverse side), CmsEmail (joined association)
        $mockListener
            ->expects($this->exactly(3))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->em->getEventManager();

        $eventManager->addEventListener([Events::postLoad], $mockListener);

        $query = $this->em->createQuery('SELECT u, e FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e WHERE u.id = :id');

        $query->setParameter('id', $this->userId);
        $query->getResult();
    }

    public function testLoadedAssociationToManyShouldTriggerEvent() : void
    {
        $mockListener = $this->createMock(PostLoadListener::class);

        // CmsUser (root), CmsAddress (ToOne inverse side), 2 CmsPhonenumber (joined association)
        $mockListener
            ->expects($this->exactly(4))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->em->getEventManager();

        $eventManager->addEventListener([Events::postLoad], $mockListener);

        $query = $this->em->createQuery('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p WHERE u.id = :id');

        $query->setParameter('id', $this->userId);
        $query->getResult();
    }

    public function testLoadedProxyEntityShouldTriggerEvent() : void
    {
        $eventManager = $this->em->getEventManager();

        // Should not be invoked during getReference call
        $mockListener = $this->createMock(PostLoadListener::class);

        $mockListener
            ->expects($this->never())
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager->addEventListener([Events::postLoad], $mockListener);

        $userProxy = $this->em->getReference(CmsUser::class, $this->userId);

        // Now deactivate original listener and attach new one
        $eventManager->removeEventListener([Events::postLoad], $mockListener);

        $mockListener2 = $this->createMock(PostLoadListener::class);

        $mockListener2
            ->expects($this->exactly(2))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager->addEventListener([Events::postLoad], $mockListener2);

        $userProxy->getName();
    }

    public function testLoadedProxyPartialShouldTriggerEvent() : void
    {
        $eventManager = $this->em->getEventManager();

        // Should not be invoked during getReference call
        $mockListener = $this->createMock(PostLoadListener::class);

        // CmsUser (partially loaded), CmsAddress (inverse ToOne), 2 CmsPhonenumber
        $mockListener
            ->expects($this->exactly(4))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager->addEventListener([Events::postLoad], $mockListener);

        $query = $this->em->createQuery('SELECT PARTIAL u.{id, name}, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p WHERE u.id = :id');

        $query->setParameter('id', $this->userId);
        $query->getResult();
    }

    public function testLoadedProxyAssociationToOneShouldTriggerEvent() : void
    {
        $user = $this->em->find(CmsUser::class, $this->userId);

        $mockListener = $this->createMock(PostLoadListener::class);

        // CmsEmail (proxy)
        $mockListener
            ->expects($this->exactly(1))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->em->getEventManager();

        $eventManager->addEventListener([Events::postLoad], $mockListener);

        $emailProxy = $user->getEmail();

        $emailProxy->getEmail();
    }

    public function testLoadedProxyAssociationToManyShouldTriggerEvent() : void
    {
        $user = $this->em->find(CmsUser::class, $this->userId);

        $mockListener = $this->createMock(PostLoadListener::class);

        // 2 CmsPhonenumber (proxy)
        $mockListener
            ->expects($this->exactly(2))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->em->getEventManager();

        $eventManager->addEventListener([Events::postLoad], $mockListener);

        $phonenumbersCol = $user->getPhonenumbers();

        $phonenumbersCol->first();
    }

    /**
     * @group DDC-3005
     */
    public function testAssociationsArePopulatedWhenEventIsFired() : void
    {
        $checkerListener = new PostLoadListenerCheckAssociationsArePopulated();
        $this->em->getEventManager()->addEventListener([Events::postLoad], $checkerListener);

        $qb = $this->em->getRepository(CmsUser::class)->createQueryBuilder('u');
        $qb->leftJoin('u.email', 'email');
        $qb->addSelect('email');
        $qb->getQuery()->getSingleResult();

        self::assertTrue($checkerListener->checked, 'postLoad event is not invoked');
        self::assertTrue($checkerListener->populated, 'Association of email is not populated in postLoad event');
    }

    /**
     * @group DDC-3005
     */
    public function testEventRaisedCorrectTimesWhenOtherEntityLoadedInEventHandler() : void
    {
        $eventManager = $this->em->getEventManager();
        $listener     = new PostLoadListenerLoadEntityInEventHandler();
        $eventManager->addEventListener([Events::postLoad], $listener);

        $this->em->find(CmsUser::class, $this->userId);
        self::assertSame(1, $listener->countHandledEvents(CmsUser::class), CmsUser::class . ' should be handled once!');
        self::assertSame(1, $listener->countHandledEvents(CmsEmail::class), CmsEmail::class . ' should be handled once!');
    }

    private function loadFixture()
    {
        $user           = new CmsUser();
        $user->name     = 'Roman';
        $user->username = 'romanb';
        $user->status   = 'developer';

        $address          = new CmsAddress();
        $address->country = 'Germany';
        $address->city    = 'Berlin';
        $address->zip     = '12345';

        $user->setAddress($address);

        $email = new CmsEmail();
        $email->setEmail('roman@domain.com');

        $user->setEmail($email);

        $ph1              = new CmsPhonenumber();
        $ph1->phonenumber = '0301234';

        $ph2              = new CmsPhonenumber();
        $ph2->phonenumber = '987654321';

        $user->addPhonenumber($ph1);
        $user->addPhonenumber($ph2);

        $this->em->persist($user);
        $this->em->flush();

        $this->userId = $user->getId();

        $this->em->clear();
    }
}

class PostLoadListener
{
    public function postLoad(LifecycleEventArgs $event)
    {
        // Expected to be mocked out
        echo 'Should never be called!';
    }
}

class PostLoadListenerCheckAssociationsArePopulated
{
    public $checked = false;

    public $populated = false;

    public function postLoad(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        if (! ($object instanceof CmsUser)) {
            return;
        }

        if ($this->checked) {
            throw new \RuntimeException('Expected to be one user!');
        }
        $this->checked   = true;
        $this->populated = $object->getEmail() !== null;
    }
}

class PostLoadListenerLoadEntityInEventHandler
{
    private $firedByClasses = [];

    public function postLoad(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        $class  = StaticClassNameConverter::getClass($object);
        if (! isset($this->firedByClasses[$class])) {
            $this->firedByClasses[$class] = 1;
        } else {
            $this->firedByClasses[$class]++;
        }
        if (! ($object instanceof CmsUser)) {
            return;
        }

        $object->getEmail()->getEmail();
    }

    public function countHandledEvents($className)
    {
        return $this->firedByClasses[$className] ?? 0;
    }
}
