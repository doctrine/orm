<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->addProperty('id', Type::getType('string'), ['id' => true]);

$metadata->setDiscriminatorColumn(
    [
        'name'             => "dtype",
        'columnDefinition' => "ENUM('ONE','TWO')"
    ]
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
