<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;

/* @var $metadata ClassMetadata */
$metadata->addProperty('id', Type::getType('string'), array(
   'id' => true,
));

$discrColumn = new Mapping\DiscriminatorColumnMetadata();

$discrColumn->setColumnName('dtype');
$discrColumn->setType(Type::getType('string'));
$discrColumn->setColumnDefinition("ENUM('ONE','TWO')");

$metadata->setDiscriminatorColumn($discrColumn);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);