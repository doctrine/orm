<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$discrColumn = new Mapping\DiscriminatorColumnMetadata();

$discrColumn->setColumnName('dtype');
$discrColumn->setType(Type::getType('string'));
$discrColumn->setColumnDefinition("ENUM('ONE','TWO')");

$metadata->setDiscriminatorColumn($discrColumn);

$metadata->setIdGeneratorType(Mapping\GeneratorType::NONE);