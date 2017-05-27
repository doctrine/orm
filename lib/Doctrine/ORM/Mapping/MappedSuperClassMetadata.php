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

/**
 * Class MappedSuperClassMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class MappedSuperClassMetadata extends ComponentMetadata
{
    /**
     * @var null|string
     */
    protected $customRepositoryClassName;

    /**
     * @var null|Property
     */
    protected $declaredVersion;

    /**
     * MappedSuperClassMetadata constructor.
     *
     * @param string                        $className
     * @param MappedSuperClassMetadata|null $parent
     */
    public function __construct(string $className, ?MappedSuperClassMetadata $parent = null)
    {
        parent::__construct($className, $parent);
    }

    /**
     * @return null|string
     */
    public function getCustomRepositoryClassName() : ?string
    {
        return $this->customRepositoryClassName;
    }

    /**
     * @param null|string customRepositoryClassName
     */
    public function setCustomRepositoryClassName(?string $customRepositoryClassName)
    {
        $this->customRepositoryClassName = $customRepositoryClassName;
    }

    /**
     * @return Property|null
     */
    public function getDeclaredVersion() : ?Property
    {
        return $this->declaredVersion;
    }

    /**
     * @param Property $property
     */
    public function setDeclaredVersion(Property $property)
    {
        $this->declaredVersion = $property;
    }

    /**
     * @return Property|null
     */
    public function getVersion() : ?Property
    {
        /** @var MappedSuperClassMetadata|null $parent */
        $parent  = $this->parent;
        $version = $this->declaredVersion;

        if ($parent && ! $version) {
            $version = $parent->getVersion();
        }

        return $version;
    }

    /**
     * @return bool
     */
    public function isVersioned() : bool
    {
        return $this->getVersion() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function addDeclaredProperty(Property $property)
    {
        parent::addDeclaredProperty($property);

        if ($property instanceof VersionFieldMetadata) {
            $this->setDeclaredVersion($property);
        }
    }
}