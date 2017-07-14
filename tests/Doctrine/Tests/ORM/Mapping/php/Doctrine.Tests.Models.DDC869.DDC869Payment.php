<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\DDC869\DDC869PaymentRepository;

/* @var $metadata ClassMetadata */
$metadata->isMappedSuperclass = true;

$fieldMetadata = new Mapping\FieldMetadata('id');
$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);
$fieldMetadata->setValueGenerator(new Mapping\ValueGeneratorMetadata(Mapping\GeneratorType::AUTO));

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('value');
$fieldMetadata->setType(Type::getType('float'));

$metadata->addProperty($fieldMetadata);

$metadata->setCustomRepositoryClassName(DDC869PaymentRepository::class);
