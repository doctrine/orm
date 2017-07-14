<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('id');
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$association = new Mapping\ManyToManyAssociationMetadata('members');

$association->setTargetEntity('DDC5934Member');

$metadata->addProperty($association);

$metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);
