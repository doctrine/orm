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

use Doctrine\ORM\Proxy\ProxyFactory;

/**
 * Special EntityManager mock used for testing purposes.
 */
class EntityManagerMock extends \Doctrine\ORM\EntityManager
{
    private $_uowMock;
    private $_proxyFactoryMock;
    private $_idGenerators = array();

    /**
     * @override
     */
    public function getUnitOfWork()
    {
        return isset($this->_uowMock) ? $this->_uowMock : parent::getUnitOfWork();
    }

    /* Mock API */

    /**
     * Sets a (mock) UnitOfWork that will be returned when getUnitOfWork() is called.
     *
     * @param <type> $uow
     */
    public function setUnitOfWork($uow)
    {
        $this->_uowMock = $uow;
    }

    public function setProxyFactory($proxyFactory)
    {
        $this->_proxyFactoryMock = $proxyFactory;
    }

    public function getProxyFactory()
    {
        return isset($this->_proxyFactoryMock) ? $this->_proxyFactoryMock : parent::getProxyFactory();
    }

    /**
     * Mock factory method to create an EntityManager.
     *
     * @param unknown_type $conn
     * @param unknown_type $name
     * @param Doctrine_Configuration $config
     * @param Doctrine_EventManager $eventManager
     * @return Doctrine\ORM\EntityManager
     */
    public static function create($conn, \Doctrine\ORM\Configuration $config = null,
            \Doctrine\Common\EventManager $eventManager = null)
    {
        if (is_null($config)) {
            $config = new \Doctrine\ORM\Configuration();
            $config->setProxyDir(__DIR__ . '/../Proxies');
            $config->setProxyNamespace('Doctrine\Tests\Proxies');
            $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(), true));
        }
        if (is_null($eventManager)) {
            $eventManager = new \Doctrine\Common\EventManager();
        }

        return new EntityManagerMock($conn, $config, $eventManager);
    }
/*
    public function setIdGenerator($className, $generator)
    {
        $this->_idGenerators[$className] = $generator;
    }
*/
    /** @override */
/*    public function getIdGenerator($className)
    {

        if (isset($this->_idGenerators[$className])) {
            return $this->_idGenerators[$className];
        }

        return parent::getIdGenerator($className);
    }
 */
}
