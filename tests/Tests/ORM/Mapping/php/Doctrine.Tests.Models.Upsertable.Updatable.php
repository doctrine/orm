<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->setPrimaryTable(
    ['name' => 'updatable_column']
);

$metadata->mapField(
    [
        'id' => true,
        'fieldName' => 'id',
    ]
);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$metadata->mapField(
    [
        'fieldName' => 'nonUpdatableContent',
        'notUpdatable' => true,
        'generated' => ClassMetadata::GENERATED_ALWAYS,
    ]
);
$metadata->mapField(
    ['fieldName' => 'updatableContent']
);
