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
use Doctrine\ORM\Persisters\EntityPersister;

/**
 * A lazy collection that allow a fast count when using criteria object
 *
 * @since   2.5
 * @author  Michaël Gallego <mic.gallego@gmail.com>
 */
class LazyCriteriaCollection extends AbstractLazyCollection implements Selectable
{
    /**
     * @var BasicEntityPersister
     */
    protected $entityPersister;

    /**
     * @var Criteria
     */
    protected $criteria;

    /**
     * @param EntityPersister $entityPersister
     * @param Criteria        $criteria
     */
    public function __construct(EntityPersister $entityPersister, Criteria $criteria)
    {
        $this->entityPersister = $entityPersister;
        $this->criteria        = $criteria;
    }

    /**
     * Do an efficient count on the collection
     *
     * @return int
     */
    public function count()
    {
        if ($this->isInitialized()) {
            return $this->collection->count();
        }

        return $this->entityPersister->count($this->criteria);
    }

    /**
     * {@inheritDoc}
     */
    protected function doInitialize()
    {
        $elements         = $this->entityPersister->loadCriteria($this->criteria);
        $this->collection = new ArrayCollection($elements);
    }

    /**
     * {@inheritDoc}
     */
    function matching(Criteria $criteria)
    {
        $this->initialize();
        return $this->collection->matching($criteria);
    }
}
