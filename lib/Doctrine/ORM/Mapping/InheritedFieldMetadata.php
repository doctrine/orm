<?php

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Types\Type;

class InheritedFieldMetadata extends FieldMetadata
{
    /**
     * @var ClassMetadata
     */
    private $currentClass;

    /**
     * FieldMetadata constructor.
     *
     * @param ClassMetadata $currentClass
     * @param ClassMetadata $declaringClass
     * @param string        $fieldName
     * @param Type          $type
     */
    public function __construct(ClassMetadata $currentClass, ClassMetadata $declaringClass, $fieldName, Type $type)
    {
        parent::__construct($declaringClass, $fieldName, $type);

        $this->currentClass = $currentClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentClass()
    {
        return $this->currentClass;
    }
}