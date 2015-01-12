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
        throw new \BadMethodCallException("Deleting elements is not supported by this CollectionPersister.");
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
}
