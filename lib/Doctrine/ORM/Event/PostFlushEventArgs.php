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
namespace Doctrine\ORM\Event;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Provides event arguments for the postFlush event.
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        www.doctrine-project.org
 * @since       2.0
 * @author      Daniel Freudenberger <df@rebuy.de>
 */
class PostFlushEventArgs extends EventArgs
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $em;

    /**
     * Map of entity changes. Keys are object ids (spl_object_hash).
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @var array
     */
    private $entityChangeSets = [];

    /**
     * Map of entities that are scheduled for dirty checking at commit time.
     * This is only used for entities with a change tracking policy of DEFERRED_EXPLICIT.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     */
    private $scheduledForSynchronization = [];

    /**
     * A list of all pending entity insertions.
     *
     * @var array
     */
    private $entityInsertions = [];

    /**
     * A list of all pending entity updates.
     *
     * @var array
     */
    private $entityUpdates = [];

    /**
     * Any pending extra updates that have been scheduled by persisters.
     *
     * @var array
     */
    private $extraUpdates = [];
    /**
     * A list of all pending entity deletions.
     *
     * @var array
     */
    private $entityDeletions = [];

    /**
     * All pending collection deletions.
     *
     * @var array
     */
    private $collectionDeletions = [];

    /**
     * All pending collection updates.
     *
     * @var array
     */
    private $collectionUpdates = [];

    /**
     * List of collections visited during changeset calculation on a commit-phase of a UnitOfWork.
     * At the end of the UnitOfWork all these collections will make new snapshots
     * of their data.
     *
     * @var array
     */
    private $visitedCollections = [];

    /**
     * Orphaned entities that are scheduled for removal.
     *
     * @var array
     */
    private $orphanRemovals = [];

    /**
     * @param EntityManagerInterface $em
     * @param array $entityInsertions
     * @param array $entityUpdates
     * @param array $entityDeletions
     * @param array $extraUpdates
     * @param array $entityChangeSets
     * @param array $collectionUpdates
     * @param array $collectionDeletions
     * @param array $visitedCollections
     * @param array $scheduledForSynchronization
     * @param array $orphanRemovals
     */
    public function __construct(
        EntityManagerInterface $em,
        array $entityInsertions = [],
        array $entityUpdates = [],
        array $entityDeletions = [],
        array $extraUpdates = [],
        array $entityChangeSets = [],
        array $collectionUpdates = [],
        array $collectionDeletions = [],
        array $visitedCollections = [],
        array $scheduledForSynchronization = [],
        array $orphanRemovals = []
    ) {
        $this->em = $em;
        $this->entityInsertions = $entityInsertions;
        $this->entityUpdates = $entityUpdates;
        $this->entityDeletions = $entityDeletions;
        $this->extraUpdates = $extraUpdates;
        $this->entityChangeSets = $entityChangeSets;
        $this->collectionUpdates = $collectionUpdates;
        $this->collectionDeletions = $collectionDeletions;
        $this->visitedCollections = $visitedCollections;
        $this->scheduledForSynchronization = $scheduledForSynchronization;
        $this->orphanRemovals = $orphanRemovals;
    }

    /**
     * Retrieves associated EntityManager.
     *
     * @return \Doctrine\ORM\EntityManagerInterface
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    /**
     * @return array
     */
    public function getEntityChangeSets(): array
    {
        return $this->entityChangeSets;
    }

    /**
     * @return array
     */
    public function getScheduledForSynchronization(): array
    {
        return $this->scheduledForSynchronization;
    }

    /**
     * @return array
     */
    public function getEntityInsertions(): array
    {
        return $this->entityInsertions;
    }

    /**
     * @return array
     */
    public function getEntityUpdates(): array
    {
        return $this->entityUpdates;
    }

    /**
     * @return array
     */
    public function getExtraUpdates(): array
    {
        return $this->extraUpdates;
    }

    /**
     * @return array
     */
    public function getEntityDeletions(): array
    {
        return $this->entityDeletions;
    }

    /**
     * @return array
     */
    public function getCollectionDeletions(): array
    {
        return $this->collectionDeletions;
    }

    /**
     * @return array
     */
    public function getCollectionUpdates(): array
    {
        return $this->collectionUpdates;
    }

    /**
     * @return array
     */
    public function getVisitedCollections(): array
    {
        return $this->visitedCollections;
    }

    /**
     * @return array
     */
    public function getOrphanRemovals(): array
    {
        return $this->orphanRemovals;
    }
}
