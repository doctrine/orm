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

/**
 * A <tt>ComponentMetadata</tt> instance holds object-relational property mapping.
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @since 3.0
 */
abstract class ComponentMetadata
{
    /**
     * @var CacheMetadata|null
     */
    protected $cache = null;

    /**
     * The ReflectionClass instance of the component class.
     *
     * @var \ReflectionClass|null
     */
    protected $reflectionClass;

    /**
     * @param CacheMetadata|null $cache
     *
     * @return void
     */
    public function setCache(?CacheMetadata $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * @return CacheMetadata|null
     */
    public function getCache(): ?CacheMetadata
    {
        return $this->cache;
    }

    /**
     * @return \ReflectionClass|null
     */
    public function getReflectionClass() : ?\ReflectionClass
    {
        return $this->reflectionClass;
    }

    /**
     * Handles metadata cloning nicely.
     */
    public function __clone()
    {
        if ($this->cache) {
            $this->cache = clone $this->cache;
        }
    }

    /**
     * Determines which fields get serialized.
     *
     * It is only serialized what is necessary for best unserialization performance.
     * That means any metadata properties that are not set or empty or simply have
     * their default value are NOT serialized.
     *
     * @return array The names of all the fields that should be serialized.
     */
    public function __sleep()
    {
        $serialized = [];

        if ($this->cache) {
            $serialized[] = 'cache';
        }

        return $serialized;
    }
}