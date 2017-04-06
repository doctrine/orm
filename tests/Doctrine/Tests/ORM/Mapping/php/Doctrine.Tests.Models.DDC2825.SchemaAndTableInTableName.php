<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

$tableMetadata = new Mapping\TableMetadata();
$tableMetadata->setSchema('implicit_schema');
$tableMetadata->setName('implicit_table');

/* @var $metadata ClassMetadata */
$metadata->setTable($tableMetadata);
$metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);

$fieldMetadata = new Mapping\FieldMetadata('id');
$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);
