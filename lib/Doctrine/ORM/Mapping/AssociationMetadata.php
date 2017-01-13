<?php

declare(strict_types = 1);

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

use Doctrine\Common\Persistence\Mapping\ReflectionService;

class AssociationMetadata implements Property
{
    /** @var ClassMetadata */
    private $declaringClass;

    /** @var \ReflectionProperty */
    private $reflection;

    /** @var string */
    private $name;

    /** @var string */
    private $fetchMode = FetchMode::LAZY;

    /** @var string */
    private $targetEntity;

    /** @var string */
    private $sourceEntity;

    /** @var string */
    private $mappedBy;

    /** @var null|string */
    private $inversedBy;

    /** @var array<string> */
    private $cascade = [];
    
    /** @var bool */
    private $owningSide = true;

    /** @var bool */
    private $orphanRemoval = false;
    
    /** @var null|CacheMetadata */
    private $cache = null;

    /**
     * {@inheritdoc}
     */
    public function getDeclaringClass()
    {
        return $this->declaringClass;
    }

    /**
     * @param ClassMetadata $declaringClass
     */
    public function setDeclaringClass(ClassMetadata $declaringClass)
    {
        $this->declaringClass = $declaringClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getTargetEntity()
    {
        return $this->targetEntity;
    }

    /**
     * @param string $targetEntity
     */
    public function setTargetEntity($targetEntity)
    {
        $this->targetEntity = $targetEntity;
    }

    /**
     * @return string
     */
    public function getSourceEntity()
    {
        return $this->sourceEntity;
    }

    /**
     * @param string $sourceEntity
     */
    public function setSourceEntity($sourceEntity)
    {
        $this->sourceEntity = $sourceEntity;
    }

    /**
     * @return array
     */
    public function getCascade()
    {
        return $this->cascade;
    }

    /**
     * @param array $cascade
     */
    public function setCascade(array $cascade)
    {
        $this->cascade = $cascade;
    }
    
    /**
     * @param bool $owningSide
     */
    public function setOwningSide(bool $owningSide)
    {
        $this->owningSide = $owningSide;
    }
    
    /**
     * @return bool
     */
    public function isOwningSide()
    {
        return $this->owningSide;
    }

    /**
     * @return string
     */
    public function getFetchMode()
    {
        return $this->fetchMode;
    }

    /**
     * @param string $fetchMode
     */
    public function setFetchMode(string $fetchMode)
    {
        $this->fetchMode = $fetchMode;
    }

    /**
     * @return string
     */
    public function getMappedBy()
    {
        return $this->mappedBy;
    }

    /**
     * @param string $mappedBy
     */
    public function setMappedBy(string $mappedBy)
    {
        $this->mappedBy = $mappedBy;
    }

    /**
     * @return null|string
     */
    public function getInversedBy()
    {
        return $this->inversedBy;
    }

    /**
     * @param null|string $inversedBy
     */
    public function setInversedBy($inversedBy)
    {
        $this->inversedBy = $inversedBy;
    }

    /**
     * @param bool $orphanRemoval
     */
    public function setOrphanRemoval(bool $orphanRemoval)
    {
        $this->orphanRemoval = $orphanRemoval;
    }

    /**
     * @return bool
     */
    public function isOrphanRemoval()
    {
        return $this->orphanRemoval;
    }
    
    /**
     * @return null|CacheMetadata
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @param null|CacheMetadata $cache
     */
    public function setCache(CacheMetadata $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($object, $value)
    {
        $this->reflection->setValue($object, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($object)
    {
        return $this->reflection->getValue($object);
    }

    /**
     * {@inheritdoc}
     */
    public function isAssociation()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isField()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function wakeupReflection(ReflectionService $reflectionService)
    {
        $this->reflection = $reflectionService->getAccessibleProperty($this->declaringClass->name, $this->name);
    }
}