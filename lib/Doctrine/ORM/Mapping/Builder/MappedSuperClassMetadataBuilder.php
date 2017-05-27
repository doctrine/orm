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

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Mapping\CacheMetadata;
use Doctrine\ORM\Mapping\Factory\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\Factory\NamingStrategy;
use Doctrine\ORM\Mapping\MappedSuperClassMetadata;
use Doctrine\ORM\Mapping\Property;

class MappedSuperClassMetadataBuilder
{
    /** @var string */
    private $className;

    /** @var null|MappedSuperClassMetadata */
    private $parent = null;

    /** @var null|CacheMetadata */
    private $cache = null;

    /** @var null|string */
    private $customRepositoryClass;

    /** @var array<Property> */
    private $properties = [];

    /** @var NamingStrategy */
    private $namingStrategy;

    public function __construct(?NamingStrategy $namingStrategy = null)
    {
        $this->namingStrategy = $namingStrategy ?: new DefaultNamingStrategy();
    }

    /**
     * @param string $className
     *
     * @return self
     */
    public function withClassName(string $className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * @param null|MappedSuperClassMetadata $parent
     *
     * @return self
     */
    public function withParent(?MappedSuperClassMetadata $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @param null|CacheMetadata $cache
     *
     * @return self
     */
    public function withCache(?CacheMetadata $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param null|string $customRepositoryClass
     *
     * @return self
     */
    public function withCustomRepositoryClass(?string $customRepositoryClass)
    {
        $this->customRepositoryClass = $customRepositoryClass;

        return $this;
    }

    /**
     * @param Property $property
     *
     * @return self
     */
    public function addProperty(Property $property)
    {
        $this->properties[$property->getName()] = $property;

        return $this;
    }

    public function build()
    {
        $mappedSuperClassMetadata = new MappedSuperClassMetadata($this->className, $this->parent);

        $mappedSuperClassMetadata->setCache($this->cache);
        $mappedSuperClassMetadata->setCustomRepositoryClass($this->customRepositoryClass);

        foreach ($this->properties as $property) {
            $mappedSuperClassMetadata->addDeclaredProperty($property);
        }

        return $mappedSuperClassMetadata;
    }
}