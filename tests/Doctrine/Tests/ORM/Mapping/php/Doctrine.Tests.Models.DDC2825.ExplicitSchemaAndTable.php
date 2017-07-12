<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

$tableMetadata = new Mapping\TableMetadata();
$tableMetadata->setSchema('explicit_schema');
$tableMetadata->setName('explicit_table');

/* @var $metadata ClassMetadata */
$metadata->setTable($tableMetadata);

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);
$fieldMetadata->setIdentifierGeneratorType(Mapping\GeneratorType::AUTO);

$metadata->addProperty($fieldMetadata);
