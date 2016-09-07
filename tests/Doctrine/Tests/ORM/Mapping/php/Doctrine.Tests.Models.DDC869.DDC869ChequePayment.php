<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('serialNumber');
$fieldMetadata->setType(Type::getType('integer'));

$metadata->addProperty($fieldMetadata);
