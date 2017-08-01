<?php


declare(strict_types=1);

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
     * @return null|string
     */
    public function getCustomRepositoryClassName() : ?string
    {
        return $this->customRepositoryClassName;
    }

    /**
     * @param null|string customRepositoryClassName
     */
    public function setCustomRepositoryClassName(?string $customRepositoryClassName) : void
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
    public function setDeclaredVersion(Property $property) : void
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
    public function addDeclaredProperty(Property $property) : void
    {
        parent::addDeclaredProperty($property);

        if ($property instanceof VersionFieldMetadata) {
            $this->setDeclaredVersion($property);
        }
    }
}
