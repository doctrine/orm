<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(
    ['name' => 'cms_users_typed']
);

$metadata->mapField(
    [
        'id' => true,
        'fieldName' => 'id',
    ]
);
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);

$metadata->mapField(
    [
        'fieldName' => 'status',
        'length' => 50,
    ]
);
$metadata->mapField(
    [
        'fieldName' => 'username',
        'length' => 255,
        'unique' => true,
    ]
);
$metadata->mapField(
    ['fieldName' => 'dateInterval']
);
$metadata->mapField(
    ['fieldName' => 'dateTime']
);
$metadata->mapField(
    ['fieldName' => 'dateTimeImmutable']
);
$metadata->mapField(
    ['fieldName' => 'array']
);
$metadata->mapField(
    ['fieldName' => 'boolean']
);
$metadata->mapField(
    ['fieldName' => 'float']
);

$metadata->mapOneToOne(
    [
        'fieldName' => 'email',
        'cascade' => [0 => 'persist'],
        'joinColumns' => [[]],
        'orphanRemoval' => true,
    ]
);

$metadata->mapManyToOne(
    ['fieldName' => 'mainEmail']
);

$metadata->mapEmbedded(['fieldName' => 'contact']);
