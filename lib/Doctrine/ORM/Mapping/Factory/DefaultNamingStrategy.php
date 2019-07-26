<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\Common\Inflector\Inflector;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;

/**
 * The default NamingStrategy
 */
class DefaultNamingStrategy implements NamingStrategy
{

    /** @var bool */
    private $plural;

    /**
     * Underscore naming strategy construct.
     *
     * @param bool $plural
     */
    public function __construct($plural = false)
    {
        $this->plural = $plural;
    }

    /**
     * @return bool
     */
    public function isPlural()
    {
        return $this->plural;
    }

    /**
     * Set naming as plural
     * Converts 'MyEntity' to 'MyEntities'
     *
     * @param bool $plural
     */
    public function setPlural($plural)
    {
        $this->plural = $plural;
    }

    /**
     * {@inheritdoc}
     */
    public function classToTableName(string $className) : string
    {
        return $this->_classToTableName($className, $this->plural);
    }

    /**
     * {@inheritdoc}
     */
    public function propertyToColumnName(string $propertyName, ?string $className = null) : string
    {
        return $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function embeddedFieldToColumnName(
        string $propertyName,
        string $embeddedColumnName,
        ?string $className = null,
        ?string $embeddedClassName = null
    ) : string {
        return $propertyName . '_' . $embeddedColumnName;
    }

    /**
     * {@inheritdoc}
     */
    public function referenceColumnName() : string
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function joinColumnName(string $propertyName, ?string $className = null) : string
    {
        return $propertyName . '_' . $this->referenceColumnName();
    }

    /**
     * {@inheritdoc}
     */
    public function joinTableName(string $sourceEntity, string $targetEntity, ?string $propertyName = null) : string
    {
        return strtolower($this->_classToTableName($sourceEntity) . '_' .
            $this->_classToTableName($targetEntity));
    }

    /**
     * {@inheritdoc}
     */
    public function joinKeyColumnName(string $entityName, ?string $referencedColumnName = null) : string
    {
        return strtolower(
            $this->_classToTableName($entityName) . '_' . ($referencedColumnName ?: $this->referenceColumnName())
        );
    }

    /**
     * @param string $string
     * @param bool $pluralize
     *
     * @return string
     */
    private function _classToTableName($className, $pluralize = false)
    {
        if (strpos($className, '\\') !== false) {
            $className = substr($className, strrpos($className, '\\') + 1);
        }

        if ($pluralize) {
            $className = Inflector::pluralize($className);
        }

        return $className;
    }
}
