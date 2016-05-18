<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Mapping\Cat;
use Doctrine\Tests\ORM\Mapping\Dog;

/* @var ClassMetadata $metadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);
$metadata->setDiscriminatorColumn(
    [
       'name'      => 'dtype',
       'type'      => 'string',
       'length'    => 255,
       'fieldName' => 'dtype',
       'tableName' => $metadata->getTableName(),
    ]
);

$metadata->setDiscriminatorMap(
    [
        'cat' => Cat::class,
        'dog' => Dog::class,
    ]
);

$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->addProperty(
    'id',
    Type::getType('string'),
    [
        'length'   => NULL,
        'nullable' => false,
        'unique'   => false,
        'id'       => true,
    ]
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);

$metadata->setCustomGeneratorDefinition(["class" => "stdClass"]);
