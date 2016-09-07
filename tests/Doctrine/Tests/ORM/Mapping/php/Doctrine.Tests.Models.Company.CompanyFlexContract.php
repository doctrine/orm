<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('hoursWorked');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('hoursWorked');

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('pricePerHour');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('pricePerHour');

$metadata->addProperty($fieldMetadata);
