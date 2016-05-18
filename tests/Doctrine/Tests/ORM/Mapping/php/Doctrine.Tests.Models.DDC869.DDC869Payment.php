<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\DDC869\DDC869PaymentRepository;

/* @var $metadata ClassMetadata */
$metadata->isMappedSuperclass = true;

$metadata->addProperty('id', Type::getType('integer'), ['id' => true]);
$metadata->addProperty('value', Type::getType('float'));

$metadata->setCustomRepositoryClass(DDC869PaymentRepository::class);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
