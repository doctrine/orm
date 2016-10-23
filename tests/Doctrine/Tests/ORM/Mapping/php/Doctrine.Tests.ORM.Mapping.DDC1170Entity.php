<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('id');
$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnDefinition('INT unsigned NOT NULL');
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('value');
$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setColumnDefinition('VARCHAR(255) NOT NULL');

$metadata->addProperty($fieldMetadata);

$metadata->setIdGeneratorType(Mapping\GeneratorType::NONE);
