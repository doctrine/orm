<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$tableMetadata = new Mapping\TableMetadata();

$tableMetadata->setSchema('explicit_schema');
$tableMetadata->setName('explicit_table');

$metadata->setPrimaryTable($tableMetadata);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);
