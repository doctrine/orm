<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->addProperty('id', Type::getType('string'), ['id' => true]);

$discrColumn = new Mapping\DiscriminatorColumnMetadata();

$discrColumn->setColumnName('dtype');
$discrColumn->setType(Type::getType('string'));
$discrColumn->setColumnDefinition("ENUM('ONE','TWO')");

$metadata->setDiscriminatorColumn($discrColumn);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
