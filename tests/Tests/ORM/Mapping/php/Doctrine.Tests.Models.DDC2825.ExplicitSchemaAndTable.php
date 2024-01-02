<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/** @var ClassMetadata $metadata */
$metadata->setPrimaryTable(
    [
        'name'   => 'explicit_table',
        'schema' => 'explicit_schema',
    ]
);

$metadata->mapField(
    [
        'id'         => true,
        'fieldName'  => 'id',
    ]
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
