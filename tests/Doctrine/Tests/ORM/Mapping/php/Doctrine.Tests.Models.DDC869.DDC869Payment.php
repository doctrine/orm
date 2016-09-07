<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\DDC869\DDC869PaymentRepository;

/* @var $metadata ClassMetadata */
$metadata->isMappedSuperclass = true;

$fieldMetadata = new Mapping\FieldMetadata('id');
$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('value');
$fieldMetadata->setType(Type::getType('float'));

$metadata->addProperty($fieldMetadata);

$metadata->setCustomRepositoryClass(DDC869PaymentRepository::class);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
