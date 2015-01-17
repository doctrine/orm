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

namespace Doctrine\ORM\Cache\Region;

use Doctrine\ORM\Cache\Lock;
use Doctrine\ORM\Cache\Region;
use Doctrine\ORM\Cache\CacheKey;
use Doctrine\ORM\Cache\CacheEntry;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Cache\MultiGetRegion;
use Doctrine\ORM\Cache\CollectionCacheEntry;

/**
 * A cache region that enables the retrieval of multiple elements with one call
 *
 * @since   2.5
 * @author  Asmir Mustafic <goetas@gmail.com>
 */
class DefaultMultiGetRegion extends DefaultRegion implements MultiGetRegion
{
    /**
     * {@inheritdoc}
     */
    public function getMulti(CollectionCacheEntry $collection)
    {
        $keysToRetrieve = array();
        foreach ($collection->identifiers as $index => $key) {
            $keysToRetrieve[$index] = $this->name . '_' . $key->hash;
        }

        $items = $this->cache->fetchMultiple($keysToRetrieve);
        if (count($items) !== count($keysToRetrieve)) {
            return null;
        }

        $returnableItems = array();
        foreach ($keysToRetrieve as $index => $key) {
            $returnableItems[$index] = $items[$key];
        }
        return $returnableItems;
    }
}
