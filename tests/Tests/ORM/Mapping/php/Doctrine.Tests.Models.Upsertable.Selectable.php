<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->setPrimaryTable(
    ['name' => 'selectable_column'],
);

$metadata->mapField(
    [
        'id' => true,
        'fieldName' => 'id',
    ],
);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$metadata->mapField(
    [
        'fieldName' => 'nonSelectableContent',
        'selectable' => false,
    ],
);
$metadata->mapField(
    ['fieldName' => 'selectableContent'],
);
