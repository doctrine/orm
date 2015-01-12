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

namespace Doctrine\ORM\Persisters;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;

/**
 * Base class for all collection persisters.
 *
 * @since 2.0
 * @author Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractCollectionPersister implements CollectionPersister
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * @var \Doctrine\ORM\UnitOfWork
     */
    protected $uow;

    /**
     * The database platform.
     *
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    protected $platform;

    /**
     * The quote strategy.
     *
     * @var \Doctrine\ORM\Mapping\QuoteStrategy
     */
    protected $quoteStrategy;

    /**
     * Initializes a new instance of a class derived from AbstractCollectionPersister.
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em               = $em;
        $this->uow              = $em->getUnitOfWork();
        $this->conn             = $em->getConnection();
        $this->platform         = $this->conn->getDatabasePlatform();
        $this->quoteStrategy    = $em->getConfiguration()->getQuoteStrategy();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();

        if ( ! $mapping['isOwningSide']) {
            return; // ignore inverse side
        }

        $this->conn->executeUpdate($this->getDeleteSQL($coll), $this->getDeleteSQLParameters($coll));
    }

    /**
     * Gets the SQL statement for deleting the given collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return string
     */
    abstract protected function getDeleteSQL(PersistentCollection $coll);

    /**
     * Gets the SQL parameters for the corresponding SQL statement to delete
     * the given collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return array
     */
    abstract protected function getDeleteSQLParameters(PersistentCollection $coll);

    /**
     * {@inheritdoc}
     */
    public function update(PersistentCollection $coll)
    {
        $mapping = $coll->getMapping();

        if ( ! $mapping['isOwningSide']) {
            return; // ignore inverse side
        }

        $this->deleteRows($coll);
        $this->insertRows($coll);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRows(PersistentCollection $coll)
    {
        $diff   = $coll->getDeleteDiff();
        $sql    = $this->getDeleteRowSQL($coll);

        foreach ($diff as $element) {
            $this->conn->executeUpdate($sql, $this->getDeleteRowSQLParameters($coll, $element));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function insertRows(PersistentCollection $coll)
    {
        $diff   = $coll->getInsertDiff();
        $sql    = $this->getInsertRowSQL($coll);

        foreach ($diff as $element) {
            $this->conn->executeUpdate($sql, $this->getInsertRowSQLParameters($coll, $element));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(PersistentCollection $coll)
    {
        throw new \BadMethodCallException("Counting the size of this persistent collection is not supported by this CollectionPersister.");
    }

    /**
     * {@inheritdoc}
     */
    public function slice(PersistentCollection $coll, $offset, $length = null)
    {
        throw new \BadMethodCallException("Slicing elements is not supported by this CollectionPersister.");
    }

    /**
     * {@inheritdoc}
     */
    public function contains(PersistentCollection $coll, $element)
    {
        throw new \BadMethodCallException("Checking for existence of an element is not supported by this CollectionPersister.");
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey(PersistentCollection $coll, $key)
    {
        throw new \BadMethodCallException("Checking for existence of a key is not supported by this CollectionPersister.");
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement(PersistentCollection $coll, $element)
    {
        throw new \BadMethodCallException("Removing an element is not supported by this CollectionPersister.");
    }

    /**
     * {@inheritdoc}
     */
    public function removeKey(PersistentCollection $coll, $key)
    {
        throw new \BadMethodCallException("Removing a key is not supported by this CollectionPersister.");
    }

    /**
     * {@inheritdoc}
     */
    public function get(PersistentCollection $coll, $index)
    {
        throw new \BadMethodCallException("Selecting a collection by index is not supported by this CollectionPersister.");
    }

    /**
     * {@inheritdoc}
     */
    public function loadCriteria(PersistentCollection $coll, Criteria $criteria)
    {
        throw new \BadMethodCallException("Filtering a collection by Criteria is not supported by this CollectionPersister.");
    }

    /**
     * Gets the SQL statement used for deleting a row from the collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return string
     */
    abstract protected function getDeleteRowSQL(PersistentCollection $coll);

    /**
     * Gets the SQL parameters for the corresponding SQL statement to delete the given
     * element from the given collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param mixed                              $element
     *
     * @return array
     */
    abstract protected function getDeleteRowSQLParameters(PersistentCollection $coll, $element);

    /**
     * Gets the SQL statement used for updating a row in the collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return string
     */
    abstract protected function getUpdateRowSQL(PersistentCollection $coll);

    /**
     * Gets the SQL statement used for inserting a row in the collection.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     *
     * @return string
     */
    abstract protected function getInsertRowSQL(PersistentCollection $coll);

    /**
     * Gets the SQL parameters for the corresponding SQL statement to insert the given
     * element of the given collection into the database.
     *
     * @param \Doctrine\ORM\PersistentCollection $coll
     * @param mixed                              $element
     *
     * @return array
     */
    abstract protected function getInsertRowSQLParameters(PersistentCollection $coll, $element);
}
