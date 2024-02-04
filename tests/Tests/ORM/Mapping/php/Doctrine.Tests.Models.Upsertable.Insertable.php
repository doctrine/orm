<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->setPrimaryTable(
    ['name' => 'insertable_column']
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
        'fieldName' => 'nonInsertableContent',
        'notInsertable' => true,
        'options' => ['default' => '1234'],
        'generated' => ClassMetadata::GENERATED_INSERT,
    ]
);
$metadata->mapField(
    ['fieldName' => 'insertableContent']
);
