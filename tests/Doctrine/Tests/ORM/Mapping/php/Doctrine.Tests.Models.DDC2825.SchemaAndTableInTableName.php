<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/** @var ClassMetadata $metadata */
$metadata->setPrimaryTable(
    ['name' => 'implicit_schema.implicit_table']
);

$metadata->mapField(
    [
        'id'         => true,
        'fieldName'  => 'id',
    ]
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
