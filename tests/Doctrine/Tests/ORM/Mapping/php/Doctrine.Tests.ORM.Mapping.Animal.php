<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Mapping\Cat;
use Doctrine\Tests\ORM\Mapping\Dog;

/** @var ClassMetadata $metadata */
$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);
$metadata->setDiscriminatorColumn(
    [
        'name' => 'dtype',
        'type' => 'string',
        'length' => 255,
        'fieldName' => 'dtype',
    ]
);
$metadata->setDiscriminatorMap(
    [
        'cat' => Cat::class,
        'dog' => Dog::class,
    ]
);
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);
$metadata->mapField(
    [
        'fieldName' => 'id',
        'type' => 'string',
        'length' => null,
        'precision' => 0,
        'scale' => 0,
        'nullable' => false,
        'unique' => false,
        'id' => true,
        'columnName' => 'id',
    ]
);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
$metadata->setCustomGeneratorDefinition(['class' => 'stdClass']);
