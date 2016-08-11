<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Tools\Export;

$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);

$metadata->setPrimaryTable(
    [
        'name'    => 'cms_users',
        'options' => [
            'engine' => 'MyISAM',
            'foo'    => ['bar' => 'baz']
        ],
    ]
);
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');

$metadata->addProperty('id', Type::getType('integer'), ['id' => true]);

$metadata->addProperty(
    'name',
    Type::getType('string'),
    [
        'length'     => 50,
        'unique'     => true,
        'nullable'   => true,
        'columnName' => 'name',
    ]
);

$metadata->addProperty(
    'email',
    Type::getType('string'),
    [
        'columnName'       => 'user_email',
        'columnDefinition' => 'CHAR(32) NOT NULL',
    ]
);

$metadata->addProperty(
    'age',
    Type::getType('integer'),
    ['options' => ['unsigned' => true]]
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

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
        'fetch'         => ClassMetadata::FETCH_EAGER,
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
        'fetch'         => ClassMetadata::FETCH_EAGER,
    ]
);

$metadata->mapOneToMany(
    [
        'fieldName'     => 'phonenumbers',
        'targetEntity'  => Export\Phonenumber::class,
        'cascade'       => ['persist', 'merge'],
        'mappedBy'      => 'user',
        'orphanRemoval' => true,
        'fetch'         => ClassMetadata::FETCH_LAZY,
        'orderBy'       => ['number' => 'ASC'],
    ]
);

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName("user_id");
$joinColumn->setReferencedColumnName("id");

$joinColumns[] = $joinColumn;

$inverseJoinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName("group_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setColumnDefinition("INT NULL");

$inverseJoinColumns[] = $joinColumn;

$joinTable = [
    'name'               => 'cms_users_groups',
    'joinColumns'        => $joinColumns,
    'inverseJoinColumns' => $inverseJoinColumns,
];

$metadata->mapManyToMany(
    [
        'fieldName'    => 'groups',
        'targetEntity' => Export\Group::class,
        'cascade'      => ['remove', 'persist', 'refresh', 'merge', 'detach'],
        'mappedBy'     => null,
        'orderBy'      => null,
        'joinTable'    => $joinTable,
        'fetch'        => ClassMetadata::FETCH_EXTRA_LAZY,
    ]
);
