<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->setPrimaryTable(array(
    'name'   => 'explicit_table',
    'schema' => 'explicit_schema',
));

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
