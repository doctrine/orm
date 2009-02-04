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

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;

/**
 * Base class for all collection persisters.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractCollectionPersister
{
    protected $_em;
    protected $_conn;
    protected $_uow;

    /**
     * Initializes a new instance of a class derived from {@link AbstractCollectionPersister}.
     *
     * @param Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_uow = $em->getUnitOfWork();
        $this->_conn = $em->getConnection();
    }

    public function recreate(PersistentCollection $coll)
    {
        if ($coll->getRelation()->isInverseSide()) {
            return;
        }
        //...
    }
    
    public function delete(PersistentCollection $coll)
    {
        if ($coll->getRelation()->isInverseSide()) {
            return;
        }
        //...
    }

    public function update(PersistentCollection $coll)
    {
        $this->deleteRows($coll);
        $this->updateRows($coll);
        $this->insertRows($coll);
    }
    
    /* collection update actions */
    
    public function deleteRows(PersistentCollection $coll)
    {
        if ($coll->getMapping()->isInverseSide()) {
            return; // ignore inverse side
        }
        
        $deleteDiff = $coll->getDeleteDiff();
        $sql = $this->_getDeleteRowSql($coll);
        $uow = $this->_em->getUnitOfWork();
        foreach ($deleteDiff as $element) {
            $this->_conn->exec($sql, $this->_getDeleteRowSqlParameters($element));
        }
    }
    
    public function updateRows(PersistentCollection $coll)
    {
        
    }
    
    public function insertRows(PersistentCollection $coll)
    {
        if ($coll->getMapping()->isInverseSide()) {
            return; // ignore inverse side
        }

        $insertDiff = $coll->getInsertDiff();
        $sql = $this->_getInsertRowSql($coll);
        $uow = $this->_em->getUnitOfWork();
        foreach ($insertDiff as $element) {
            $this->_conn->exec($sql/*, $uow->getEntityIdentifier($element)*/);
        }
    }

    /**
     * Gets the SQL statement used for deleting a row from the collection.
     * 
     * @param PersistentCollection $coll
     */
    abstract protected function _getDeleteRowSql(PersistentCollection $coll);

    abstract protected function _getDeleteRowSqlParameters(PersistentCollection $coll, $element);

    /**
     * Gets the SQL statement used for updating a row in the collection.
     *
     * @param PersistentCollection $coll
     */
    abstract protected function _getUpdateRowSql();

    /**
     * Gets the SQL statement used for inserting a row from to the collection.
     *
     * @param PersistentCollection $coll
     */
    abstract protected function _getInsertRowSql();
}

