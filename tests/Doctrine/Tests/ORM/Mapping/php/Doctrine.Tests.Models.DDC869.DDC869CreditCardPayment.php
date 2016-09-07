<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('creditCardNumber');
$fieldMetadata->setType(Type::getType('string'));

$metadata->addProperty($fieldMetadata);
