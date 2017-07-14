<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('id');
$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('user_id');
$fieldMetadata->setPrimaryKey(true);
$fieldMetadata->setValueGenerator(new Mapping\ValueGeneratorMetadata(Mapping\GeneratorType::AUTO));

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('name');
$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setLength(250);
$fieldMetadata->setColumnName('user_name');
$fieldMetadata->setNullable(true);
$fieldMetadata->setUnique(false);

$metadata->addProperty($fieldMetadata);

$association = new Mapping\ManyToManyAssociationMetadata('groups');

$association->setTargetEntity(\Doctrine\Tests\Models\DDC3579\DDC3579Group::class);

$metadata->addProperty($association);
