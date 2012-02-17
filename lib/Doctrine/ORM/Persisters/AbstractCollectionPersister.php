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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Persisters;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\PersistentCollection;

/**
 * Base class for all collection persisters.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractCollectionPersister
{
    /**
     * @var EntityManager
     */
    protected $_em;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $_conn;

    /**
     * @var \Doctrine\ORM\UnitOfWork
     */
    protected $_uow;

    /**
     * Initializes a new instance of a class derived from AbstractCollectionPersister.
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
        $this->_uow = $em->getUnitOfWork();
        $this->_conn = $em->getConnection();
    }

    /**
     * Deletes the persistent state represented by the given collection.
     *
     * @param PersistentCollection $coll
     */
    public function delete(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        
        if ( ! $mapping['isOwningSide']) {
            return; // ignore inverse side
        }
        
        $sql = $this->_getDeleteSQL($coll);
        $this->_conn->executeUpdate($sql, $this->_getDeleteSQLParameters($coll));
    }

    /**
     * Gets the SQL statement for deleting the given collection.
     *
     * @param PersistentCollection $coll
     */
    abstract protected function _getDeleteSQL(PersistentCollection $coll);

    /**
     * Gets the SQL parameters for the corresponding SQL statement to delete
     * the given collection.
     *
     * @param PersistentCollection $coll
     */
    abstract protected function _getDeleteSQLParameters(PersistentCollection $coll);

    /**
     * Updates the given collection, synchronizing it's state with the database
     * by inserting, updating and deleting individual elements.
     *
     * @param PersistentCollection $coll
     */
    public function update(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();
        
        if ( ! $mapping['isOwningSide']) {
            return; // ignore inverse side
        }
        
        $this->deleteRows($coll);
        //$this->updateRows($coll);
        $this->insertRows($coll);
    }
    
    public function deleteRows(PersistentCollection $coll)
    {        
        $deleteDiff = $coll->getDeleteDiff();
        $sql = $this->_getDeleteRowSQL($coll);
        
        foreach ($deleteDiff as $element) {
            $this->_conn->executeUpdate($sql, $this->_getDeleteRowSQLParameters($coll, $element));
        }
    }
    
    //public function updateRows(PersistentCollection $coll)
    //{}
    
    public function insertRows(PersistentCollection $coll)
    {
        $insertDiff = $coll->getInsertDiff();
        $sql = $this->_getInsertRowSQL($coll);
        
        foreach ($insertDiff as $element) {
            $this->_conn->executeUpdate($sql, $this->_getInsertRowSQLParameters($coll, $element));
        }
    }

    public function count(PersistentCollection $coll)
    {
        throw new \BadMethodCallException("Counting the size of this persistent collection is not supported by this CollectionPersister.");
    }

    public function slice(PersistentCollection $coll, $offset, $length = null)
    {
        throw new \BadMethodCallException("Slicing elements is not supported by this CollectionPersister.");
    }

    public function contains(PersistentCollection $coll, $element)
    {
        throw new \BadMethodCallException("Checking for existance of an element is not supported by this CollectionPersister.");
    }

    public function containsKey(PersistentCollection $coll, $key)
    {
        throw new \BadMethodCallException("Checking for existance of a key is not supported by this CollectionPersister.");
    }

    public function get(PersistentCollection $coll, $index)
    {
        throw new \BadMethodCallException("Selecting a collection by index is not supported by this CollectionPersister.");
    }

    /**
     * Gets the SQL statement used for deleting a row from the collection.
     * 
     * @param PersistentCollection $coll
     */
    abstract protected function _getDeleteRowSQL(PersistentCollection $coll);

    /**
     * Gets the SQL parameters for the corresponding SQL statement to delete the given
     * element from the given collection.
     *
     * @param PersistentCollection $coll
     * @param mixed $element
     */
    abstract protected function _getDeleteRowSQLParameters(PersistentCollection $coll, $element);

    /**
     * Gets the SQL statement used for updating a row in the collection.
     *
     * @param PersistentCollection $coll
     */
    abstract protected function _getUpdateRowSQL(PersistentCollection $coll);

    /**
     * Gets the SQL statement used for inserting a row in the collection.
     *
     * @param PersistentCollection $coll
     */
    abstract protected function _getInsertRowSQL(PersistentCollection $coll);

    /**
     * Gets the SQL parameters for the corresponding SQL statement to insert the given
     * element of the given collection into the database.
     *
     * @param PersistentCollection $coll
     * @param mixed $element
     */
    abstract protected function _getInsertRowSQLParameters(PersistentCollection $coll, $element);
}
