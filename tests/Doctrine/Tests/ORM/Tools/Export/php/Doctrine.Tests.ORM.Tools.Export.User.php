<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\Tests\ORM\Tools\Export;

$tableMetadata = new Mapping\TableMetadata();
$tableMetadata->setName('cms_users');
$tableMetadata->addOption('engine', 'MyISAM');
$tableMetadata->addOption('foo', ['bar' => 'baz']);

$metadata->setPrimaryTable($tableMetadata);
$metadata->setIdGeneratorType(Mapping\GeneratorType::AUTO);
$metadata->setInheritanceType(Mapping\InheritanceType::NONE);
$metadata->setChangeTrackingPolicy(Mapping\ChangeTrackingPolicy::DEFERRED_IMPLICIT);

$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');

// Property: "id"
$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

// Property: "name"
$fieldMetadata = new Mapping\FieldMetadata('name');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setLength(50);
$fieldMetadata->setColumnName('name');
$fieldMetadata->setNullable(true);
$fieldMetadata->setUnique(true);

$metadata->addProperty($fieldMetadata);

// Property: "email"
$fieldMetadata = new Mapping\FieldMetadata('email');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setColumnName('user_email');
$fieldMetadata->setColumnDefinition('CHAR(32) NOT NULL');

$metadata->addProperty($fieldMetadata);

// Property: "age"
$fieldMetadata = new Mapping\FieldMetadata('age');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setOptions(['unsigned' => true]);

$metadata->addProperty($fieldMetadata);

// Property: "mainGroup"
$association = new Mapping\ManyToOneAssociationMetadata('mainGroup');

$association->setTargetEntity(Export\Group::class);

$metadata->addAssociation($association);

// Property: "address"
$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("address_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setOnDelete("CASCADE");

$joinColumns[] = $joinColumn;

$association = new Mapping\OneToOneAssociationMetadata('address');

$association->setJoinColumns($joinColumns);
$association->setTargetEntity(Export\Address::class);
$association->setInversedBy('user');
$association->setCascade(['persist']);
$association->setFetchMode(Mapping\FetchMode::EAGER);
$association->setOrphanRemoval(true);

$metadata->addAssociation($association);

// Property: "cart"
$association = new Mapping\OneToOneAssociationMetadata('cart');

$association->setTargetEntity(Export\Cart::class);
$association->setMappedBy('user');
$association->setCascade(['persist']);
$association->setFetchMode(Mapping\FetchMode::EAGER);
$association->setOrphanRemoval(false);

$metadata->addAssociation($association);

// Property: "phonenumbers"
$association = new Mapping\OneToManyAssociationMetadata('phonenumbers');

$association->setTargetEntity(Export\Phonenumber::class);
$association->setMappedBy('user');
$association->setCascade(['persist', 'merge']);
$association->setFetchMode(Mapping\FetchMode::LAZY);
$association->setOrphanRemoval(true);
$association->setOrderBy(['number' => 'ASC']);

$metadata->addAssociation($association);

// Property: "groups"
$joinTable = new Mapping\JoinTableMetadata();
$joinTable->setName('cms_users_groups');

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName("user_id");
$joinColumn->setReferencedColumnName("id");

$joinTable->addJoinColumn($joinColumn);

$inverseJoinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName("group_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setColumnDefinition("INT NULL");

$joinTable->addInverseJoinColumn($joinColumn);

$association = new Mapping\ManyToManyAssociationMetadata('groups');

$association->setJoinTable($joinTable);
$association->setTargetEntity(Export\Group::class);
$association->setCascade(['remove', 'persist', 'refresh', 'merge', 'detach']);
$association->setFetchMode(Mapping\FetchMode::EXTRA_LAZY);

$metadata->addAssociation($association);
