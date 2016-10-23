<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->isMappedSuperclass = true;

$fieldMetadata = new Mapping\FieldMetadata('name');

$fieldMetadata->setType(Type::getType('string'));

$metadata->addProperty($fieldMetadata);

$metadata->setCustomRepositoryClass("Doctrine\Tests\Models\DDC889\DDC889SuperClass");
$metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);