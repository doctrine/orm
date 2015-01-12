<?php
namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

/**
 * PostLoadEventTest
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class PostLoadEventTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * @var integer
     */
    private $userId;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->useModelSet('cms');

        parent::setUp();

        $this->loadFixture();
    }

    public function testLoadedEntityUsingFindShouldTriggerEvent()
    {
        $mockListener = $this->getMock('Doctrine\Tests\ORM\Functional\PostLoadListener');

        // CmsUser and CmsAddres, because it's a ToOne inverse side on CmsUser
        $mockListener
            ->expects($this->exactly(2))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->_em->getEventManager();

        $eventManager->addEventListener(array(Events::postLoad), $mockListener);

        $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
    }

    public function testLoadedEntityUsingQueryShouldTriggerEvent()
    {
        $mockListener = $this->getMock('Doctrine\Tests\ORM\Functional\PostLoadListener');

        // CmsUser and CmsAddres, because it's a ToOne inverse side on CmsUser
        $mockListener
            ->expects($this->exactly(2))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->_em->getEventManager();

        $eventManager->addEventListener(array(Events::postLoad), $mockListener);

        $query = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id');

        $query->setParameter('id', $this->userId);
        $query->getResult();
    }

    public function testLoadedAssociationToOneShouldTriggerEvent()
    {
        $mockListener = $this->getMock('Doctrine\Tests\ORM\Functional\PostLoadListener');

        // CmsUser (root), CmsAddress (ToOne inverse side), CmsEmail (joined association)
        $mockListener
            ->expects($this->exactly(3))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->_em->getEventManager();

        $eventManager->addEventListener(array(Events::postLoad), $mockListener);

        $query = $this->_em->createQuery('SELECT u, e FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.email e WHERE u.id = :id');

        $query->setParameter('id', $this->userId);
        $query->getResult();
    }

    public function testLoadedAssociationToManyShouldTriggerEvent()
    {
        $mockListener = $this->getMock('Doctrine\Tests\ORM\Functional\PostLoadListener');

        // CmsUser (root), CmsAddress (ToOne inverse side), 2 CmsPhonenumber (joined association)
        $mockListener
            ->expects($this->exactly(4))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->_em->getEventManager();

        $eventManager->addEventListener(array(Events::postLoad), $mockListener);

        $query = $this->_em->createQuery('SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p WHERE u.id = :id');

        $query->setParameter('id', $this->userId);
        $query->getResult();
    }

    public function testLoadedProxyEntityShouldTriggerEvent()
    {
        $eventManager = $this->_em->getEventManager();

        // Should not be invoked during getReference call
        $mockListener = $this->getMock('Doctrine\Tests\ORM\Functional\PostLoadListener');

        $mockListener
            ->expects($this->never())
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager->addEventListener(array(Events::postLoad), $mockListener);

        $userProxy = $this->_em->getReference('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);

        // Now deactivate original listener and attach new one
        $eventManager->removeEventListener(array(Events::postLoad), $mockListener);

        $mockListener2 = $this->getMock('Doctrine\Tests\ORM\Functional\PostLoadListener');

        $mockListener2
            ->expects($this->exactly(2))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager->addEventListener(array(Events::postLoad), $mockListener2);

        $userProxy->getName();
    }

    public function testLoadedProxyPartialShouldTriggerEvent()
    {
        $eventManager = $this->_em->getEventManager();

        // Should not be invoked during getReference call
        $mockListener = $this->getMock('Doctrine\Tests\ORM\Functional\PostLoadListener');

        // CmsUser (partially loaded), CmsAddress (inverse ToOne), 2 CmsPhonenumber
        $mockListener
            ->expects($this->exactly(4))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager->addEventListener(array(Events::postLoad), $mockListener);

        $query = $this->_em->createQuery('SELECT PARTIAL u.{id, name}, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p WHERE u.id = :id');

        $query->setParameter('id', $this->userId);
        $query->getResult();
    }

    public function testLoadedProxyAssociationToOneShouldTriggerEvent()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);

        $mockListener = $this->getMock('Doctrine\Tests\ORM\Functional\PostLoadListener');

        // CmsEmail (proxy)
        $mockListener
            ->expects($this->exactly(1))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->_em->getEventManager();

        $eventManager->addEventListener(array(Events::postLoad), $mockListener);

        $emailProxy = $user->getEmail();

        $emailProxy->getEmail();
    }

    public function testLoadedProxyAssociationToManyShouldTriggerEvent()
    {
        $user = $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);

        $mockListener = $this->getMock('Doctrine\Tests\ORM\Functional\PostLoadListener');

        // 2 CmsPhonenumber (proxy)
        $mockListener
            ->expects($this->exactly(2))
            ->method('postLoad')
            ->will($this->returnValue(true));

        $eventManager = $this->_em->getEventManager();

        $eventManager->addEventListener(array(Events::postLoad), $mockListener);

        $phonenumbersCol = $user->getPhonenumbers();

        $phonenumbersCol->first();
    }

    /**
     * @group DDC-3005
     */
    public function testAssociationsArePopulatedWhenEventIsFired()
    {
        $checkerListener = new PostLoadListenerCheckAssociationsArePopulated();
        $this->_em->getEventManager()->addEventListener(array(Events::postLoad), $checkerListener);

        $qb = $this->_em->getRepository('Doctrine\Tests\Models\CMS\CmsUser')->createQueryBuilder('u');
        $qb->leftJoin('u.email', 'email');
        $qb->addSelect('email');
        $qb->getQuery()->getSingleResult();

        $this->assertTrue($checkerListener->checked, 'postLoad event is not invoked');
        $this->assertTrue($checkerListener->populated, 'Association of email is not populated in postLoad event');
    }

    /**
     * @group DDC-3005
     */
    public function testEventRaisedCorrectTimesWhenOtherEntityLoadedInEventHandler()
    {
        $eventManager = $this->_em->getEventManager();
        $listener = new PostLoadListenerLoadEntityInEventHandler();
        $eventManager->addEventListener(array(Events::postLoad), $listener);

        $this->_em->find('Doctrine\Tests\Models\CMS\CmsUser', $this->userId);
        $this->assertSame(1, $listener->countHandledEvents('Doctrine\Tests\Models\CMS\CmsUser'), 'Doctrine\Tests\Models\CMS\CmsUser should be handled once!');
        $this->assertSame(1, $listener->countHandledEvents('Doctrine\Tests\Models\CMS\CmsEmail'), '\Doctrine\Tests\Models\CMS\CmsEmail should be handled once!');
    }

    private function loadFixture()
    {
        $user = new CmsUser;
        $user->name = 'Roman';
        $user->username = 'romanb';
        $user->status = 'developer';

        $address = new CmsAddress;
        $address->country = 'Germany';
        $address->city = 'Berlin';
        $address->zip = '12345';

        $user->setAddress($address);

        $email = new CmsEmail;
        $email->setEmail('roman@domain.com');

        $user->setEmail($email);

        $ph1 = new CmsPhonenumber;
        $ph1->phonenumber = "0301234";

        $ph2 = new CmsPhonenumber;
        $ph2->phonenumber = "987654321";

        $user->addPhonenumber($ph1);
        $user->addPhonenumber($ph2);

        $this->_em->persist($user);
        $this->_em->flush();

        $this->userId = $user->getId();

        $this->_em->clear();
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
        if ($object instanceof CmsUser) {
            if ($this->checked) {
                throw new \RuntimeException('Expected to be one user!');
            }
            $this->checked = true;
            $this->populated = null !== $object->getEmail();
        }
    }
}

class PostLoadListenerLoadEntityInEventHandler
{
    private $firedByClasses = array();

    public function postLoad(LifecycleEventArgs $event)
    {
        $object = $event->getObject();
        $class = ClassUtils::getClass($object);
        if (!isset($this->firedByClasses[$class])) {
            $this->firedByClasses[$class] = 1;
        } else {
            $this->firedByClasses[$class]++;
        }
        if ($object instanceof CmsUser) {
            $object->getEmail()->getEmail();
        }
    }

    public function countHandledEvents($className)
    {
        return isset($this->firedByClasses[$className]) ? $this->firedByClasses[$className] : 0;
    }
}
