<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->addProperty(
    'id',
    Type::getType('string'),
    [
        'id'               => true,
        'columnDefinition' => 'INT unsigned NOT NULL',
    ]
);

$metadata->addProperty(
    'value',
    Type::getType('string'),
    ['columnDefinition' => 'VARCHAR(255) NOT NULL']
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
