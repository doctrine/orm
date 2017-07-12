<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('id');
$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setPrimaryKey(true);
$fieldMetadata->setIdentifierGeneratorType(Mapping\GeneratorType::NONE);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('name');
$fieldMetadata->setType(Type::getType('string'));

$metadata->addProperty($fieldMetadata);
