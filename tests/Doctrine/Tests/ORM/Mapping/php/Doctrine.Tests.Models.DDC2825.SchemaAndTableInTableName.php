<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->setPrimaryTable(['name' => 'implicit_schema.implicit_table']);

$metadata->addProperty('id', Type::getType('integer'), ['id' => true]);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
