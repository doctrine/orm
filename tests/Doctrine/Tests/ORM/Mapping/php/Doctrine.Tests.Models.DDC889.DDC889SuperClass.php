<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->isMappedSuperclass = true;

$fieldMetadata = new Mapping\FieldMetadata('name');
$fieldMetadata->setType(Type::getType('string'));

$metadata->addProperty($fieldMetadata);

$metadata->setCustomRepositoryClass(DDC889SuperClass::class);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
