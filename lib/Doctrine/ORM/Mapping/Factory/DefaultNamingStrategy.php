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
    public function classToTableName($className)
    {
        return $this->_classToTableName($className, $this->plural);
    }

    /**
     * {@inheritdoc}
     */
    public function propertyToColumnName($propertyName, $className = null)
    {
        return $propertyName;
    }

    /**
     * {@inheritdoc}
     */
    public function embeddedFieldToColumnName($propertyName, $embeddedColumnName, $className = null, $embeddedClassName = null)
    {
        return $propertyName . '_' . $embeddedColumnName;
    }

    /**
     * {@inheritdoc}
     */
    public function referenceColumnName()
    {
        return 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function joinColumnName($propertyName, $className = null)
    {
        return $propertyName . '_' . $this->referenceColumnName();
    }

    /**
     * {@inheritdoc}
     */
    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null)
    {
        return strtolower($this->_classToTableName($sourceEntity) . '_' .
            $this->_classToTableName($targetEntity));
    }

    /**
     * {@inheritdoc}
     */
    public function joinKeyColumnName($entityName, $referencedColumnName = null)
    {
        return strtolower($this->_classToTableName($entityName) . '_' .
            ($referencedColumnName ?: $this->referenceColumnName()));
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
