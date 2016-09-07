<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('serialNumber');

$fieldMetadata->setType(Type::getType('integer'));

$metadata->addProperty($fieldMetadata);
