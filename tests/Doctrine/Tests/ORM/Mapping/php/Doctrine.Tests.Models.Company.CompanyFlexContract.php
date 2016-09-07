<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('hoursWorked');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('hoursWorked');

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('pricePerHour');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('pricePerHour');

$metadata->addProperty($fieldMetadata);
