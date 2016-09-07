<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->setPrimaryTable(
    [
        'name'   => 'explicit_table',
        'schema' => 'explicit_schema',
    ]
);

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
