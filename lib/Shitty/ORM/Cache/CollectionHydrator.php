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

namespace Shitty\ORM\Cache;

use Shitty\ORM\PersistentCollection;
use Shitty\ORM\Mapping\ClassMetadata;

/**
 * Hydrator cache entry for collections
 *
 * @since   2.5
 * @author  Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
interface CollectionHydrator
{
    /**
     * @param \Shitty\ORM\Mapping\ClassMetadata           $metadata   The entity metadata.
     * @param \Shitty\ORM\Cache\CollectionCacheKey        $key        The cached collection key.
     * @param array|\Shitty\Common\Collections\Collection $collection The collection.
     *
     * @return \Shitty\ORM\Cache\CollectionCacheEntry
     */
    public function buildCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, $collection);

    /**
     * @param \Shitty\ORM\Mapping\ClassMetadata      $metadata   The owning entity metadata.
     * @param \Shitty\ORM\Cache\CollectionCacheKey   $key        The cached collection key.
     * @param \Shitty\ORM\Cache\CollectionCacheEntry $entry      The cached collection entry.
     * @param \Shitty\ORM\PersistentCollection       $collection The collection to load the cache into.
     *
     * @return array
     */
    public function loadCacheEntry(ClassMetadata $metadata, CollectionCacheKey $key, CollectionCacheEntry $entry, PersistentCollection $collection);
}
