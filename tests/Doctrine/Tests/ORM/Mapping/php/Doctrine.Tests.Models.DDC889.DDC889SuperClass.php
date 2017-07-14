<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->isMappedSuperclass = true;

$fieldMetadata = new Mapping\FieldMetadata('name');
$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setValueGenerator(new Mapping\ValueGeneratorMetadata(Mapping\GeneratorType::AUTO));

$metadata->addProperty($fieldMetadata);

$metadata->setCustomRepositoryClassName(DDC889SuperClass::class);
