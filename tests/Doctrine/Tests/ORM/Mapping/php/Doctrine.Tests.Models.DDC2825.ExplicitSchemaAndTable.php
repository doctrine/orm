<?php

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */

$metadata->setPrimaryTable(array(
    'name'   => 'explicit_table',
    'schema' => 'explicit_schema',
));

$metadata->mapField(array(
    'id'         => true,
    'fieldName'  => 'id',
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
