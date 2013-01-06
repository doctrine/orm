<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\DDC1734\DDC1734Article;

require_once __DIR__ . '/../../../TestInit.php';

class DDC1734Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        $this->useModelSet('ddc1734');
        parent::setUp();
    }

    /**
     * This test is DDC-1734 minus the serialization, i.e. it works
     * @group DDC-1734
     */
    public function testMergeWorksOnNonSerializedProxies()
    {
        $article = new DDC1734Article("Foo");
        $this->_em->persist($article);
        $this->_em->flush();
        // Get a proxy of the entity
        $this->_em->clear();
        $proxy = $this->getProxy($article);
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $proxy);
        $this->assertFalse($proxy->__isInitialized__);
        // Detach
        $this->_em->detach($proxy);
        $this->_em->clear();
        // Merge
        $proxy = $this->_em->merge($proxy);
        $this->assertEquals("Foo", $proxy->getName(), "The entity is broken");
    }

    /**
     * This test reproduces DDC-1734 which is:
     * - A non-initialized proxy is detached and serialized (the identifier of the proxy is *not* serialized)
     * - the object is deserialized and merged (to turn into an entity)
     * - the entity is broken because it has no identifier and no field defined
     * @group DDC-1734
     */
    public function testMergeWorksOnSerializedProxies()
    {
        $article = new DDC1734Article("Foo");
        $this->_em->persist($article);
        $this->_em->flush();
        // Get a proxy of the entity
        $this->_em->clear();
        $proxy = $this->getProxy($article);
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $proxy);
        $this->assertFalse($proxy->__isInitialized());
        // Detach and serialize
        $this->_em->detach($proxy);
        $serializedProxy = serialize($proxy);
        $this->_em->clear();
        // Unserialize and merge
        /** @var $unserializedProxy DDC1734Article */
        $unserializedProxy = unserialize($serializedProxy);
        // Merge
        $unserializedProxy = $this->_em->merge($unserializedProxy);
        $this->assertEquals("Foo", $unserializedProxy->getName(), "The entity is broken");
    }

    private function getProxy($object)
    {
        $metadataFactory = $this->_em->getMetadataFactory();
        $identifier      = $metadataFactory->getMetadataFor(get_class($object))->getIdentifierValues($object);
        $proxyFactory    = $this->_em->getProxyFactory();

        return $proxyFactory->getProxy(get_class($object), $identifier);
    }

}