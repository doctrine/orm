<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\Event\InitializeProxyEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\OrmFunctionalTestCase;
use function get_class;

class InitializeProxyEventTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    public function testEventIsCalledOnProxyInitialization()
    {
        $listener = new InitializeProxyListener();
        $this->_em->getEventManager()->addEventListener(Events::initializeProxy, $listener);

        // Prerequisite: create, persist and flush an entity
        $group       = new CmsGroup();
        $group->name = 'name';
        $this->_em->persist($group);
        $this->_em->flush();
        // Prerequisite: get a proxy for the persisted entity
        $proxy = $this->getProxy($group);

        // Action: this triggers the proxy initialization
        $proxy->getName();

        // Expectation: initialization event has been called
        $this->assertTrue($listener->called);
    }

    /**
     * @param object $object
     *
     * @return Proxy
     */
    private function getProxy($object)
    {
        $metadataFactory = $this->_em->getMetadataFactory();
        $className       = get_class($object);
        $identifier      = $metadataFactory->getMetadataFor($className)->getIdentifierValues($object);

        return $this->_em->getProxyFactory()->getProxy($className, $identifier);
    }
}

class InitializeProxyListener
{
    public $called = false;

    public function initializeProxy(InitializeProxyEventArgs $args)
    {
        $this->called = true;
    }
}
