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

namespace Doctrine\ORM\Cache;

/**
 * Collection cache entry
 */
class CollectionCacheEntry implements CacheEntry
{
    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var CacheKey[] The list of entity identifiers hold by the collection
     */
    public $identifiers;

    /**
     * @param CacheKey[] $identifiers List of entity identifiers hold by the collection
     */
    public function __construct(array $identifiers)
    {
        $this->identifiers = $identifiers;
    }

    /**
     * Creates a new CollectionCacheEntry
     *
     * This method allows for Doctrine\Common\Cache\PhpFileCache compatibility
     *
     * @param array<string, mixed> $values array containing property values
     *
     * @return CollectionCacheEntry
     */
    public static function __set_state(array $values)
    {
        return new self($values['identifiers']);
    }
}
