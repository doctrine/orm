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

use Doctrine\ORM\EntityManagerInterface;

use function array_map;

/**
 * Entity cache entry
 */
class EntityCacheEntry implements CacheEntry
{
    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var array<string,mixed> The entity map data
     */
    public $data;

    /**
     * READ-ONLY: Public only for performance reasons, it should be considered immutable.
     *
     * @var string The entity class name
     * @psalm-var class-string
     */
    public $class;

    /**
     * @param string              $class The entity class.
     * @param array<string,mixed> $data  The entity data.
     * @psalm-param class-string $class
     */
    public function __construct($class, array $data)
    {
        $this->class = $class;
        $this->data  = $data;
    }

    /**
     * Creates a new EntityCacheEntry
     *
     * This method allow Doctrine\Common\Cache\PhpFileCache compatibility
     *
     * @param array<string,mixed> $values array containing property values
     *
     * @return EntityCacheEntry
     */
    public static function __set_state(array $values)
    {
        return new self($values['class'], $values['data']);
    }

    /**
     * Retrieves the entity data resolving cache entries
     *
     * @return array<string, mixed>
     */
    public function resolveAssociationEntries(EntityManagerInterface $em)
    {
        return array_map(static function ($value) use ($em) {
            if (! ($value instanceof AssociationCacheEntry)) {
                return $value;
            }

            return $em->getReference($value->class, $value->identifier);
        }, $this->data);
    }
}
