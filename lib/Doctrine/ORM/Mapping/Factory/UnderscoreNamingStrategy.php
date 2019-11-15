<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

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

    /**
     * Underscore naming strategy construct.
     *
     * @param int $case CASE_LOWER | CASE_UPPER
     */
    public function __construct($case = CASE_LOWER)
    {
        $this->case = $case;
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
     * {@inheritdoc}
     */
    public function classToTableName(string $className) : string
    {
        if (\strpos($className, '\\') !== false) {
            $className = \substr($className, \strrpos($className, '\\') + 1);
        }

        return $this->underscore($className);
    }

    /**
     * {@inheritdoc}
     */
    public function propertyToColumnName(string $propertyName, ?string $className = null) : string
    {
        return $this->underscore($propertyName);
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
        return $this->underscore($propertyName) . '_' . $embeddedColumnName;
    }

    /**
     * {@inheritdoc}
     */
    public function referenceColumnName() : string
    {
        return $this->case === CASE_UPPER ? 'ID' : 'id';
    }

    /**
     * {@inheritdoc}
     */
    public function joinColumnName(string $propertyName, ?string $className = null) : string
    {
        return $this->underscore($propertyName) . '_' . $this->referenceColumnName();
    }

    /**
     * {@inheritdoc}
     */
    public function joinTableName(string $sourceEntity, string $targetEntity, ?string $propertyName = null) : string
    {
        return $this->classToTableName($sourceEntity) . '_' . $this->classToTableName($targetEntity);
    }

    /**
     * {@inheritdoc}
     */
    public function joinKeyColumnName(string $entityName, ?string $referencedColumnName = null) : string
    {
        return $this->classToTableName($entityName) . '_' .
                ($referencedColumnName ?: $this->referenceColumnName());
    }

    private function underscore(string $string) : string
    {
        $string = \preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $string);

        if ($this->case === CASE_UPPER) {
            return \strtoupper($string);
        }

        return \strtolower($string);
    }
}
