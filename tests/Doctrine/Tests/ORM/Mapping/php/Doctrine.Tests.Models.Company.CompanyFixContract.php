<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('fixPrice');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('fixPrice');

$metadata->addProperty($fieldMetadata);
