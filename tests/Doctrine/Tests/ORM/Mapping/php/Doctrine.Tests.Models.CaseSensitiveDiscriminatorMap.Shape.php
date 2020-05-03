<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Tests\Models\CaseSensitiveDiscriminatorMap\cube;

$metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE);
$metadata->setDiscriminatorColumn([
    'name' => 'discr',
    'type' => 'string',
    'length' => 32,
]);
$metadata->setDiscriminatorMap([
    'cube' => cube::class,
]);
$metadata->mapField([
    'fieldName' => 'id',
    'type' => 'string',
    'length' => null,
    'precision' => 0,
    'scale' => 0,
    'nullable' => false,
    'unique' => false,
    'id' => true,
    'columnName' => 'id',
]);
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
