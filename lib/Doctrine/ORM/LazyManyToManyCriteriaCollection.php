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

namespace Doctrine\ORM;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Persisters\BasicEntityPersister;
use Doctrine\ORM\Persisters\CollectionPersister;
use Doctrine\ORM\Persisters\EntityPersister;

/**
 * A lazy collection for many to many associations that is created when using the
 * Criteria API. It allows to delay the actual execution of SQL queries and hence
 * doing optimized queries for things like COUNT, without having to loading the
 * full collection
 *
 * @since   2.5
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 */
class LazyManyToManyCriteriaCollection extends AbstractLazyCollection implements Selectable
{
    /**
     * @var CollectionPersister
     */
    protected $collectionPersister;

    /**
     * @var Criteria
     */
    protected $criteria;

    /**
     * @var integer
     */
    private $count;

    /**
     * @param PersistentCollection $persistentCollection
     * @param CollectionPersister  $collectionPersister
     * @param Criteria             $criteria
     */
    public function __construct(
        PersistentCollection $persistentCollection,
        CollectionPersister $collectionPersister,
        Criteria $criteria
    ) {
        $this->collection           = $persistentCollection;
        $this->collectionPersister  = $collectionPersister;
        $this->criteria             = $criteria;
    }

    /**
     * Do an efficient count on the collection
     *
     * @return integer
     */
    public function count()
    {
        if ($this->isInitialized()) {
            return $this->collection->count();
        }

        // Return cached result in case count query was already executed
        if ($this->count !== null) {
            return $this->count;
        }

        return $this->count = $this->collectionPersister->count($this->collection, $this->criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function matching(Criteria $criteria)
    {
        $this->initialize();

        return $this->collection->matching($criteria);
    }

    /**
     * {@inheritDoc}
     */
    protected function doInitialize()
    {
        $elements         = $this->collectionPersister->loadCriteria($this->collection, $this->criteria);
        $this->collection = new ArrayCollection($elements);
    }
}
