<?php
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\Mocks;

use Doctrine\Common\EventManager;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;

/**
 * Special EntityManager mock used for testing purposes.
 */
class EntityManagerMock extends EntityManager
{
    /**
     * @var \Doctrine\ORM\UnitOfWork|null
     */
    private $_uowMock;

    /**
     * @var \Doctrine\ORM\Proxy\ProxyFactory|null
     */
    private $_proxyFactoryMock;

    /**
     * {@inheritdoc}
     */
    public function getUnitOfWork()
    {
        return isset($this->_uowMock) ? $this->_uowMock : parent::getUnitOfWork();
    }

    /* Mock API */

    /**
     * Sets a (mock) UnitOfWork that will be returned when getUnitOfWork() is called.
     *
     * @param \Doctrine\ORM\UnitOfWork $uow
     *
     * @return void
     */
    public function setUnitOfWork($uow)
    {
        $this->_uowMock = $uow;
    }

    /**
     * @param \Doctrine\ORM\Proxy\ProxyFactory $proxyFactory
     *
     * @return void
     */
    public function setProxyFactory($proxyFactory)
    {
        $this->_proxyFactoryMock = $proxyFactory;
    }

    /**
     * @return \Doctrine\ORM\Proxy\ProxyFactory
     */
    public function getProxyFactory()
    {
        return isset($this->_proxyFactoryMock) ? $this->_proxyFactoryMock : parent::getProxyFactory();
    }

    /**
     * Mock factory method to create an EntityManager.
     *
     * {@inheritdoc}
     */
    public static function create($conn, Configuration $config = null, EventManager $eventManager = null)
    {
        if (null === $config) {
            $config = new Configuration();
            $config->setProxyDir(__DIR__ . '/../Proxies');
            $config->setProxyNamespace('Doctrine\Tests\Proxies');
            $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(), true));
        }
        if (null === $eventManager) {
            $eventManager = new EventManager();
        }

        return new EntityManagerMock($conn, $config, $eventManager);
    }
}
