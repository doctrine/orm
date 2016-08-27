<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Mapping\Address;
use Doctrine\Tests\ORM\Mapping\Group;
use Doctrine\Tests\ORM\Mapping\Phonenumber;

$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(['name' => 'cms_users']);
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');

$metadata->addNamedQuery(
    [
        'name'  => 'all',
        'query' => 'SELECT u FROM __CLASS__ u'
    ]
);

$metadata->addProperty(
    'id',
    Type::getType('integer'),
    [
        'id'      => true,
        'options' => ['foo' => 'bar', 'unsigned' => false],
    ]
);

$metadata->addProperty(
    'name',
    Type::getType('string'),
    [
        'length'     => 50,
        'unique'     => true,
        'nullable'   => true,
        'columnName' => 'name',
        'options'    => [
            'foo' => 'bar',
            'baz' => ['key' => 'val'],
            'fixed' => false
        ],
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

$metadata->setVersionProperty($metadata->addProperty('version', Type::getType('integer')));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName('address_id');
$joinColumn->setReferencedColumnName('id');
$joinColumn->setOnDelete('CASCADE');

$joinColumns[] = $joinColumn;

$metadata->mapOneToOne(
    [
        'fieldName'     => 'address',
        'targetEntity'  => Group::class,
        'cascade'       => ['remove'],
        'mappedBy'      => NULL,
        'inversedBy'    => 'user',
        'joinColumns'   => $joinColumns,
        'orphanRemoval' => false,
    ]
);

$metadata->mapOneToMany(
    [
        'fieldName'     => 'phonenumbers',
        'targetEntity'  => Phonenumber::class,
        'cascade'       => ['persist'],
        'mappedBy'      => 'user',
        'orphanRemoval' => true,
        'orderBy'       => ['number' => 'ASC'],
    ]
);

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName("user_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setNullable(false);
$joinColumn->setUnique(false);

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
        'targetEntity' => Group::class,
        'cascade'      => ['remove', 'persist', 'refresh', 'merge', 'detach'],
        'mappedBy'     => null,
        'joinTable'    => $joinTable,
        'orderBy'      => null,
    ]
);

$metadata->table['options'] = [
    'foo' => 'bar',
    'baz' => ['key' => 'val']
];

$metadata->table['uniqueConstraints'] = [
    'search_idx' => [
        'columns' => ['name', 'user_email'],
        'options' => ['where' => 'name IS NOT NULL']
    ],
];

$metadata->table['indexes'] = [
    'name_idx' => ['columns' => ['name']],
    0 => ['columns' => ['user_email']]
];

$metadata->setSequenceGeneratorDefinition(
    [
        'sequenceName' => 'tablename_seq',
        'allocationSize' => 100,
        'initialValue' => 1,
    ]
);
