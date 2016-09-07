<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('creditCardNumber');

$fieldMetadata->setType(Type::getType('string'));

$metadata->addProperty($fieldMetadata);
