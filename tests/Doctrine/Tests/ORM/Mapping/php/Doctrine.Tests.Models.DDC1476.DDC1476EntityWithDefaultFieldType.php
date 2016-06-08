<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->addProperty('id', Type::getType('string'), ['id' => true]);
$metadata->addProperty('name', Type::getType('string'));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
