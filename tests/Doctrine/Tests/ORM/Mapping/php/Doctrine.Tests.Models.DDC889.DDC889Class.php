<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/** @var ClassMetadata $metadata */
$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);
