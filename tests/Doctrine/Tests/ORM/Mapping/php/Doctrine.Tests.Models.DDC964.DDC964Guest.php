<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('guest_id');
$fieldMetadata->setPrimaryKey(true);

$metadata->setAttributeOverride($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('name');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setLength(240);
$fieldMetadata->setColumnName('guest_name');
$fieldMetadata->setNullable(false);
$fieldMetadata->setUnique(true);

$metadata->setAttributeOverride($fieldMetadata);
