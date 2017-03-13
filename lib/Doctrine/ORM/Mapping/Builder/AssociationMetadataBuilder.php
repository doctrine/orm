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

use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\CacheMetadata;
use Doctrine\ORM\Mapping\FetchMode;

abstract class AssociationMetadataBuilder implements Builder
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $targetEntity;

    /** @var string */
    private $sourceEntity;

    /** @var string */
    private $mappedBy;

    /** @var null|string */
    private $inversedBy;

    /** @var string */
    private $fetchMode = FetchMode::LAZY;

    /** @var array<string> */
    private $cascade = [];

    /** @var boolean */
    protected $primaryKey = false;

    /** @var bool */
    private $orphanRemoval = false;

    /** @var null|CacheMetadata */
    private $cache = null;

    public function __construct()
    {
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function withName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $targetEntity
     *
     * @return self
     */
    public function withTargetEntity(string $targetEntity)
    {
        $this->targetEntity = $targetEntity;

        return $this;
    }

    /**
     * @param string $sourceEntity
     *
     * @return self
     */
    public function withSourceEntity(string $sourceEntity)
    {
        $this->sourceEntity = $sourceEntity;

        return $this;
    }

    /**
     * @param string $mappedBy
     *
     * @return self
     */
    public function withMappedBy(string $mappedBy)
    {
        $this->mappedBy = $mappedBy;

        return $this;
    }

    /**
     * @param null|string $inversedBy
     *
     * @return self
     */
    public function withInversedBy(string $inversedBy = null)
    {
        $this->inversedBy = $inversedBy;

        return $this;
    }

    /**
     * @param string $fetchMode
     *
     * @return self
     */
    public function withFetchMode(string $fetchMode)
    {
        $this->fetchMode = $fetchMode;

        return $this;
    }

    /**
     * @param array $cascade
     *
     * @return self
     */
    public function withCascade(array $cascade)
    {
        $this->cascade = $cascade;

        return $this;
    }

    /**
     * @param bool $primaryKey
     *
     * @return self
     */
    public function withPrimaryKey(bool $primaryKey)
    {
        $this->primaryKey = $primaryKey;

        return $this;
    }

    /**
     * @param bool $orphanRemoval
     *
     * @return self
     */
    public function withOrphanRemoval(bool $orphanRemoval)
    {
        $this->orphanRemoval = $orphanRemoval;

        return $this;
    }

    /**
     * @param null|CacheMetadata $cache
     *
     * @return self
     */
    public function withCache(CacheMetadata $cache = null)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @return AssociationMetadata
     */
    public function build()
    {
        $associationMetadata = $this->createMetadataObject();

        $associationMetadata->setName($this->name);
        $associationMetadata->setTargetEntity($this->targetEntity);

        if ($this->sourceEntity !== null) {
            $associationMetadata->setSourceEntity($this->sourceEntity);
        }

        if ($this->mappedBy !== null) {
            $associationMetadata->setMappedBy($this->mappedBy);
        }

        if ($this->inversedBy !== null) {
            $associationMetadata->setInversedBy($this->inversedBy);
        }

        $associationMetadata->setFetchMode($this->fetchMode);
        $associationMetadata->setCascade($this->cascade);
        $associationMetadata->setPrimaryKey($this->primaryKey);
        $associationMetadata->setOrphanRemoval($this->orphanRemoval);
        $associationMetadata->setCache($this->cache);

        return $associationMetadata;
    }

    /**
     * @return AssociationMetadata
     */
    abstract protected function createMetadataObject();
}
