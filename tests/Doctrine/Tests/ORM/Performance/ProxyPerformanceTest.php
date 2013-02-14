<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\ORM\Performance;

use Doctrine\Tests\OrmPerformanceTestCase;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Persisters\BasicEntityPersister;

/**
 * Performance test used to measure performance of proxy instantiation
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 * @group performance
 */
class ProxyPerformanceTest extends OrmPerformanceTestCase
{
    /**
     * @return array
     */
    public function entitiesProvider()
    {
        return array(
            array('Doctrine\Tests\Models\CMS\CmsEmployee'),
            array('Doctrine\Tests\Models\CMS\CmsUser'),
        );
    }

    /**
     * @dataProvider entitiesProvider
     */
    public function testProxyInstantiationPerformance($entityName)
    {
        $proxyFactory = $this->_getEntityManager()->getProxyFactory();
        $this->setMaxRunningTime(5);
        $start = microtime(true);

        for ($i = 0; $i < 100000; $i += 1) {
            $user = $proxyFactory->getProxy($entityName, array('id' => $i));
        }

        echo __FUNCTION__ . " - " . (microtime(true) - $start) . " seconds with " . $entityName . PHP_EOL;
    }

    /**
     * @dataProvider entitiesProvider
     */
    public function testProxyForcedInitializationPerformance($entityName)
    {
        $em              = new MockEntityManager($this->_getEntityManager());
        $proxyFactory    = $em->getProxyFactory();
        /* @var $user \Doctrine\Common\Proxy\Proxy */
        $user            = $proxyFactory->getProxy($entityName, array('id' => 1));
        $initializer     = $user->__getInitializer();

        $this->setMaxRunningTime(5);
        $start = microtime(true);

        for ($i = 0; $i < 100000;  $i += 1) {
            $user->__setInitialized(false);
            $user->__setInitializer($initializer);
            $user->__load();
            $user->__load();
        }

        echo __FUNCTION__ . " - " . (microtime(true) - $start) . " seconds with " . $entityName . PHP_EOL;
    }
}

/**
 * Mock entity manager to fake `getPersister()`
 */
class MockEntityManager extends EntityManager
{
    /** @var EntityManager */
    private $em;

    /** @param EntityManager $em */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /** {@inheritDoc} */
    public function getProxyFactory()
    {
        $config = $this->em->getConfiguration();

        return new ProxyFactory(
            $this,
            $config->getProxyDir(),
            $config->getProxyNamespace(),
            $config->getAutoGenerateProxyClasses()
        );
    }

    /** {@inheritDoc} */
    public function getClassMetadata($className)
    {
        return $this->em->getClassMetadata($className);
    }

    /** {@inheritDoc} */
    public function getUnitOfWork()
    {
        return new MockUnitOfWork();
    }
}

/**
 * Mock UnitOfWork manager to fake `getPersister()`
 */
class MockUnitOfWork extends UnitOfWork
{
    /** @var PersisterMock */
    private $entityPersister;

    /** */
    public function __construct()
    {
        $this->entityPersister = new PersisterMock();
    }

    /** {@inheritDoc} */
    public function getEntityPersister($entityName)
    {
        return $this->entityPersister;
    }
}

/**
 * Mock persister (we don't want PHPUnit comparator API to play a role in here)
 */
class PersisterMock extends BasicEntityPersister
{
    /** */
    public function __construct()
    {
    }

    /** {@inheritDoc} */
    public function load(array $criteria, $entity = null, $assoc = null, array $hints = array(), $lockMode = 0, $limit = null, array $orderBy = null)
    {
        return $entity;
    }
}