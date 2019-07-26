<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

use Doctrine\Common\Inflector\Inflector;
use const CASE_LOWER;
use const CASE_UPPER;
use function preg_replace;
use function strpos;
use function strrpos;
use function strtolower;
use function strtoupper;
use function substr;

/**
 * Naming strategy implementing the underscore naming convention.
 * Converts 'MyEntity' to 'my_entity' or 'MY_ENTITY'.
 */
class UnderscoreNamingStrategy implements NamingStrategy
{
    /** @var int */
    private $case;

    /** @var bool */
    private $plural;

    /**
     * Underscore naming strategy construct.
     *
     * @param int $case CASE_LOWER | CASE_UPPER
     * @param bool $plural
     */
    public function __construct($case = CASE_LOWER, $plural = false)
    {
        $this->case = $case;
        $this->plural = $plural;
    }

    /**
     * @return int CASE_LOWER | CASE_UPPER
     */
    public function getCase()
    {
        return $this->case;
    }

    /**
     * Sets string case CASE_LOWER | CASE_UPPER.
     * Alphabetic characters converted to lowercase or uppercase.
     *
     * @param int $case
     */
    public function setCase($case)
    {
        $this->case = $case;
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
     * Converts 'MyEntity' to 'my_entities' or 'MY_ENTITIES'.
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
        return $this->underscore($propertyName);
    }

    /**
     * {@inheritdoc}
     */
    public function embeddedFieldToColumnName($propertyName, $embeddedColumnName, $className = null, $embeddedClassName = null)
    {
        return $this->underscore($propertyName) . '_' . $embeddedColumnName;
    }

    /**
     * {@inheritdoc}
     */
    public function referenceColumnName()
    {
        return $this->case === CASE_UPPER ? 'ID' : 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function joinColumnName($propertyName, $className = null)
    {
        return $this->underscore($propertyName) . '_' . $this->referenceColumnName();
    }

    /**
     * {@inheritdoc}
     */
    public function joinTableName($sourceEntity, $targetEntity, $propertyName = null)
    {
        return $this->_classToTableName($sourceEntity) . '_' . $this->_classToTableName($targetEntity);
    }

    /**
     * {@inheritdoc}
     */
    public function joinKeyColumnName($entityName, $referencedColumnName = null)
    {
        return $this->_classToTableName($entityName) . '_' .
                ($referencedColumnName ?: $this->referenceColumnName());
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

        return $this->underscore($className);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function underscore($string)
    {
        $string = preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $string);

        if ($this->case === CASE_UPPER) {
            return strtoupper($string);
        }

        return strtolower($string);
    }
}
