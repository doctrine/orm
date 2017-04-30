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

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;

class ToManyAssociationMetadata extends AssociationMetadata
{
    /** @var array<string, string> */
    private $orderBy = [];
    
    /** @var null|string */
    private $indexedBy;

    /**
     * @param array $orderBy
     */
    public function setOrderBy(array $orderBy)
    {
        $this->orderBy = $orderBy;
    }

    /**
     * @return array
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param null|string $indexedBy
     */
    public function setIndexedBy(string $indexedBy = null)
    {
        $this->indexedBy = $indexedBy;
    }

    /**
     * @return null|string
     */
    public function getIndexedBy()
    {
        return $this->indexedBy;
    }

    /**
     * @param object                 $owner
     * @param null|array|Collection  $collection
     * @param EntityManagerInterface $entityManager
     *
     * @return PersistentCollection
     */
    public function wrap($owner, $collection, EntityManagerInterface $entityManager)
    {
        if ($collection instanceof PersistentCollection) {
            if ($collection->getOwner() === $owner) {
                return $collection;
            }

            $collection = $collection->getValues();
        }

        // If $value is not a Collection then use an ArrayCollection.
        if (! $collection instanceof Collection) {
            // @todo guilhermeblanco Conceptually, support to custom collections by replacing ArrayCollection creation.
            $collection = new ArrayCollection((array) $collection);
        }

        // Inject PersistentCollection
        $targetClass = $entityManager->getClassMetadata($this->getTargetEntity());
        $collection  = new PersistentCollection($entityManager, $targetClass, $collection);

        $collection->setOwner($owner, $this);
        $collection->setDirty( ! $collection->isEmpty());
        $collection->setInitialized(true);

        return $collection;
    }
}