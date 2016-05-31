<?php

use Doctrine\DBAL\Types\Type;
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

$metadata->setVersionMetadata($metadata->addProperty('version', Type::getType('integer')));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$metadata->mapOneToOne(
    [
        'fieldName' => 'address',
        'targetEntity' => Address::class,
        'cascade' => [0 => 'remove'],
        'mappedBy' => null,
        'inversedBy' => 'user',
        'joinColumns' => [
            0 => [
                'name' => 'address_id',
                'referencedColumnName' => 'id',
                'onDelete' => 'CASCADE',
            ],
        ],
        'orphanRemoval' => false,
    ]
);

$metadata->mapOneToMany(
    [
        'fieldName' => 'phonenumbers',
        'targetEntity' => Phonenumber::class,
        'cascade' => [1 => 'persist'],
        'mappedBy' => 'user',
        'orphanRemoval' => true,
        'orderBy' => ['number' => 'ASC'],
    ]
);

$metadata->mapManyToMany(
    [
        'fieldName' => 'groups',
        'targetEntity' => Group::class,
        'cascade' => [
            0 => 'remove',
            1 => 'persist',
            2 => 'refresh',
            3 => 'merge',
            4 => 'detach',
        ],
        'mappedBy' => null,
        'joinTable' => [
            'name' => 'cms_users_groups',
            'joinColumns' => [
                0 => [
                    'name' => 'user_id',
                    'referencedColumnName' => 'id',
                    'unique' => false,
                    'nullable' => false,
                ],
            ],
            'inverseJoinColumns' => [
                0 => [
                    'name' => 'group_id',
                    'referencedColumnName' => 'id',
                    'columnDefinition' => 'INT NULL',
                ],
            ],
        ],
        'orderBy' => null,
    ]
);

$metadata->table['options'] = [
    'foo' => 'bar',
    'baz' => ['key' => 'val']
];

$metadata->table['uniqueConstraints'] = [
    'search_idx' => ['columns' => ['name', 'user_email'], 'options' => ['where' => 'name IS NOT NULL']],
];

$metadata->table['indexes'] = [
    'name_idx' => ['columns' => ['name']], 0 => ['columns' => ['user_email']]
];

$metadata->setSequenceGeneratorDefinition(
    [
        'sequenceName' => 'tablename_seq',
        'allocationSize' => 100,
        'initialValue' => 1,
    ]
);
