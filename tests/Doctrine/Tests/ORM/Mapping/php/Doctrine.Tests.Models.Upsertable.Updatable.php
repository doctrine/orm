<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->setPrimaryTable(
    ['name' => 'updatable_column']
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
        'fieldName' => 'nonUpdatableContent',
        'notUpdatable' => true,
        'generated' => ClassMetadataInfo::GENERATED_ALWAYS,
    ]
);
$metadata->mapField(
    ['fieldName' => 'updatableContent']
);
