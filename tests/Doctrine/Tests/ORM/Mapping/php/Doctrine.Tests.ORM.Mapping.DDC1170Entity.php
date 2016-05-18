<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->addProperty('id', Type::getType('string'), array(
    'id'               => true,
    'columnDefinition' => 'INT unsigned NOT NULL',
));

$metadata->addProperty('value', Type::getType('string'), array(
    'columnDefinition' => 'VARCHAR(255) NOT NULL'
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);