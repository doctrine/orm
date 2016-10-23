<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->isMappedSuperclass = true;

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('value');

$fieldMetadata->setType(Type::getType('float'));

$metadata->addProperty($fieldMetadata);

$metadata->setCustomRepositoryClass("Doctrine\Tests\Models\DDC869\DDC869PaymentRepository");
$metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);