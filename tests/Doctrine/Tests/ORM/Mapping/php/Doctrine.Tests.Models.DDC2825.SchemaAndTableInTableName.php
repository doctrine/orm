<?php

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */

$metadata->setPrimaryTable(
    [
    'name' => 'implicit_schema.implicit_table',
    ]
);

$metadata->mapField(
    [
    'id'         => true,
    'fieldName'  => 'id',
    ]
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
