<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setColumnName('user_id');
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('name');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setLength(250);
$fieldMetadata->setColumnName('user_name');
$fieldMetadata->setNullable(true);
$fieldMetadata->setUnique(false);

$metadata->addProperty($fieldMetadata);

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName('address_id');
$joinColumn->setReferencedColumnName('id');

$joinColumns[] = $joinColumn;

$association = new Mapping\ManyToOneAssociationMetadata('address');

$association->setJoinColumns($joinColumns);
$association->setTargetEntity('DDC964Address');
$association->setCascade(['persist', 'merge']);

$metadata->addAssociation($association);

$joinTable = new Mapping\JoinTableMetadata();
$joinTable->setName('ddc964_users_groups');

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName("user_id");
$joinColumn->setReferencedColumnName("id");

$joinTable->addJoinColumn($joinColumn);

$inverseJoinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName("group_id");
$joinColumn->setReferencedColumnName("id");

$joinTable->addInverseJoinColumn($joinColumn);

$association = new Mapping\ManyToManyAssociationMetadata('groups');

$association->setJoinTable($joinTable);
$association->setTargetEntity('DDC964Group');
$association->setInversedBy('user');
$association->setCascade(['persist','merge','detach']);

$metadata->addAssociation($association);

$metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);
