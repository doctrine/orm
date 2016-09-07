<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('fixPrice');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('fixPrice');

$metadata->addProperty($fieldMetadata);