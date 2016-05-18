<?php

use Doctrine\DBAL\Types\Type;
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
        'fieldName' => 'mainGroup',
        'targetEntity' => Export\Group::class,
    ]
);

$metadata->mapOneToOne(
    [
        'fieldName' => 'address',
        'targetEntity' => Export\Address::class,
        'inversedBy' => 'user',
        'cascade' => ['persist'],
        'mappedBy' => null,
        'joinColumns' => [
            0 => [
                'name' => 'address_id',
                'referencedColumnName' => 'id',
                'onDelete' => 'CASCADE',
            ],
        ],
        'orphanRemoval' => true,
        'fetch' => ClassMetadata::FETCH_EAGER,
    ]
);

$metadata->mapOneToOne(
    [
        'fieldName' => 'cart',
        'targetEntity' => Export\Cart::class,
        'mappedBy' => 'user',
        'cascade' => [0 => 'persist'],
        'inversedBy' => null,
        'orphanRemoval' => false,
        'fetch' => ClassMetadata::FETCH_EAGER,
    ]
);

$metadata->mapOneToMany(
    [
        'fieldName' => 'phonenumbers',
        'targetEntity' => Export\Phonenumber::class,
        'cascade' => ['persist', 'merge'],
        'mappedBy' => 'user',
        'orphanRemoval' => true,
        'fetch' => ClassMetadata::FETCH_LAZY,
        'orderBy' => ['number' => 'ASC'],
    ]
);

$metadata->mapManyToMany(
    [
        'fieldName' => 'groups',
        'targetEntity' => Export\Group::class,
        'fetch' => ClassMetadata::FETCH_EXTRA_LAZY,
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
        'orderBy' => NULL,
    ]
);
