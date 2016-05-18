<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$metadata->setPrimaryTable(array(
    'name' => 'implicit_schema.implicit_table',
));

$metadata->addProperty('id', Type::getType('integer'), array(
    'id' => true,
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
