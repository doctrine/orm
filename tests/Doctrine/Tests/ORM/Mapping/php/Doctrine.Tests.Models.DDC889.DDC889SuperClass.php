<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->isMappedSuperclass = true;

$metadata->addProperty('name', Type::getType('string'));

$metadata->setCustomRepositoryClass(DDC889SuperClass::class);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
