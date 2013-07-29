<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION); HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE); ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * EntityManager interface
 *
 * @since   2.4
 * @author  Lars Strojny <lars@strojny.net
 */
interface EntityManagerInterface extends ObjectManager
{
    public function getConnection();
    public function getExpressionBuilder();
    public function beginTransaction();
    public function transactional($func);
    public function commit();
    public function rollback();
    public function createQuery($dql = '');
    public function createNamedQuery($name);
    public function createNativeQuery($sql, ResultSetMapping $rsm);
    public function createNamedNativeQuery($name);
    public function createQueryBuilder();
    public function getReference($entityName, $id);
    public function getPartialReference($entityName, $identifier);
    public function close();
    public function copy($entity, $deep = false);
    public function lock($entity, $lockMode, $lockVersion = null);
    public function getEventManager();
    public function getConfiguration();
    public function isOpen();
    public function getUnitOfWork();
    public function getHydrator($hydrationMode);
    public function newHydrator($hydrationMode);
    public function getProxyFactory();
    public function getFilters();
    public function isFiltersStateClean();
    public function hasFilters();
}
