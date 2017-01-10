<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\CMS\CmsGroup;

class DDC1734Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /**
     * This test is DDC-1734 minus the serialization, i.e. it works
     *
     * @group DDC-1734
     */
    public function testMergeWorksOnNonSerializedProxies()
    {
        $group = new CmsGroup();

        $group->setName('Foo');
        $this->em->persist($group);
        $this->em->flush();
        $this->em->clear();

        $proxy = $this->getProxy($group);

        self::assertInstanceOf(Proxy::class, $proxy);
        self::assertFalse($proxy->__isInitialized());

        $this->em->detach($proxy);
        $this->em->clear();

        $proxy = $this->em->merge($proxy);

        self::assertEquals('Foo', $proxy->getName(), 'The entity is broken');
    }

    /**
     * This test reproduces DDC-1734 which is:
     * - A non-initialized proxy is detached and serialized (the identifier of the proxy is *not* serialized)
     * - the object is deserialized and merged (to turn into an entity)
     * - the entity is broken because it has no identifier and no field defined
     *
     * @group DDC-1734
     */
    public function testMergeWorksOnSerializedProxies()
    {
        $group = new CmsGroup();

        $group->setName('Foo');
        $this->em->persist($group);
        $this->em->flush();
        $this->em->clear();

        $proxy = $this->getProxy($group);

        self::assertInstanceOf(Proxy::class, $proxy);
        self::assertFalse($proxy->__isInitialized());

        $this->em->detach($proxy);
        $serializedProxy = serialize($proxy);
        $this->em->clear();

        $unserializedProxy = $this->em->merge(unserialize($serializedProxy));
        self::assertEquals('Foo', $unserializedProxy->getName(), 'The entity is broken');
    }

    /**
     * @param object $object
     *
     * @return \Doctrine\Common\Proxy\Proxy
     */
    private function getProxy($object)
    {
        $metadataFactory = $this->em->getMetadataFactory();
        $className       = get_class($object);
        $identifier      = $metadataFactory->getMetadataFor($className)->getIdentifierValues($object);

        return $this->em->getProxyFactory()->getProxy($className, $identifier);
    }

}
