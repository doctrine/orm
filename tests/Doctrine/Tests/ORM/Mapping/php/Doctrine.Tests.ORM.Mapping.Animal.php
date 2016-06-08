<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Mapping\Cat;
use Doctrine\Tests\ORM\Mapping\Dog;

/* @var $metadata ClassMetadata */
$metadata->setInheritanceType(Mapping\ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);

$discrColumn = new Mapping\DiscriminatorColumnMetadata();

$discrColumn->setTableName($metadata->getTableName());
$discrColumn->setColumnName('dtype');
$discrColumn->setType(Type::getType('string'));
$discrColumn->setLength(255);

$metadata->setDiscriminatorColumn($discrColumn);

$metadata->setDiscriminatorMap(
    [
        'cat' => Cat::class,
        'dog' => Dog::class,
    ]
);

$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->addProperty(
    'id',
    Type::getType('integer'),
    [
        'length'   => NULL,
        'nullable' => false,
        'unique'   => false,
        'id'       => true,
    ]
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);

$metadata->setCustomGeneratorDefinition(["class" => "stdClass"]);
