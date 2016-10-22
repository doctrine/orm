<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Tools\Export;

$tableMetadata = new Mapping\TableMetadata();
$tableMetadata->setName('cms_users');
$tableMetadata->addOption('engine', 'MyISAM');
$tableMetadata->addOption('foo', ['bar' => 'baz']);

$metadata->setPrimaryTable($tableMetadata);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->setInheritanceType(Mapping\InheritanceType::NONE);
$metadata->setChangeTrackingPolicy(Mapping\ChangeTrackingPolicy::DEFERRED_IMPLICIT);

$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');


$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);


$fieldMetadata = new Mapping\FieldMetadata('name');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setLength(50);
$fieldMetadata->setColumnName('name');
$fieldMetadata->setNullable(true);
$fieldMetadata->setUnique(true);

$metadata->addProperty($fieldMetadata);


$fieldMetadata = new Mapping\FieldMetadata('email');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setColumnName('user_email');
$fieldMetadata->setColumnDefinition('CHAR(32) NOT NULL');

$metadata->addProperty($fieldMetadata);


$fieldMetadata = new Mapping\FieldMetadata('age');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setOptions(['unsigned' => true]);

$metadata->addProperty($fieldMetadata);


$metadata->mapManyToOne(
    [
        'fieldName'    => 'mainGroup',
        'targetEntity' => Export\Group::class,
    ]
);

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("address_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setOnDelete("CASCADE");

$joinColumns[] = $joinColumn;

$metadata->mapOneToOne(
    [
        'fieldName'     => 'address',
        'targetEntity'  => Export\Address::class,
        'inversedBy'    => 'user',
        'cascade'       => ['persist'],
        'mappedBy'      => null,
        'joinColumns'   => $joinColumns,
        'orphanRemoval' => true,
        'fetch'         => Mapping\FetchMode::EAGER,
    ]
);

$metadata->mapOneToOne(
    [
        'fieldName'     => 'cart',
        'targetEntity'  => Export\Cart::class,
        'mappedBy'      => 'user',
        'cascade'       => ['persist'],
        'inversedBy'    => null,
        'orphanRemoval' => false,
        'fetch'         => Mapping\FetchMode::EAGER,
    ]
);

$metadata->mapOneToMany(
    [
        'fieldName'     => 'phonenumbers',
        'targetEntity'  => Export\Phonenumber::class,
        'cascade'       => ['persist', 'merge'],
        'mappedBy'      => 'user',
        'orphanRemoval' => true,
        'fetch'         => Mapping\FetchMode::LAZY,
        'orderBy'       => ['number' => 'ASC'],
    ]
);

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

$metadata->mapManyToMany(
    [
        'fieldName'    => 'groups',
        'targetEntity' => Export\Group::class,
        'cascade'      => ['remove', 'persist', 'refresh', 'merge', 'detach'],
        'mappedBy'     => null,
        'orderBy'      => null,
        'joinTable'    => $joinTable,
        'fetch'        => Mapping\FetchMode::EXTRA_LAZY,
    ]
);
