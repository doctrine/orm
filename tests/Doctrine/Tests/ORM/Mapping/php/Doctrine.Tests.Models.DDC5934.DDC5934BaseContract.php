<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('id');
$fieldMetadata->setPrimaryKey(true);
$fieldMetadata->setIdentifierGeneratorType(Mapping\GeneratorType::AUTO);

$metadata->addProperty($fieldMetadata);

$association = new Mapping\ManyToManyAssociationMetadata('members');

$association->setTargetEntity(\Doctrine\Tests\Models\DDC5934\DDC5934Member::class);

$metadata->addProperty($association);
