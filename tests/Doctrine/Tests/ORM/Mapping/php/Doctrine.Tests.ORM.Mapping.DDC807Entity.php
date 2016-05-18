<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->addProperty('id', Type::getType('string'), array(
   'id' => true,
));

$metadata->setDiscriminatorColumn(array(
    'name'             => "dtype",
    'columnDefinition' => "ENUM('ONE','TWO')"
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);