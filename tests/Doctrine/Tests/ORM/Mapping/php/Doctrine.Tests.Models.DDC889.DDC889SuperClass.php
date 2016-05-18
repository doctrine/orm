<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->isMappedSuperclass = true;

$metadata->addProperty('name', Type::getType('string'));

$metadata->setCustomRepositoryClass("Doctrine\Tests\Models\DDC889\DDC889SuperClass");
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);