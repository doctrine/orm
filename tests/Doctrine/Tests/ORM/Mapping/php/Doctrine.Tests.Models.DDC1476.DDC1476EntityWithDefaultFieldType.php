<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->addProperty('id', Type::getType('string'), array(
   'id' => true,
));

$metadata->addProperty('name', Type::getType('string'));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);