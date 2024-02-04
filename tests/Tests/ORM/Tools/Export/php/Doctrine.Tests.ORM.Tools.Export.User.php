<?php

declare(strict_types=1);

use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\ORM\Tools\Export;
use Doctrine\Tests\ORM\Tools\Export\Address;
use Doctrine\Tests\ORM\Tools\Export\AddressListener;
use Doctrine\Tests\ORM\Tools\Export\Cart;
use Doctrine\Tests\ORM\Tools\Export\Group;
use Doctrine\Tests\ORM\Tools\Export\GroupListener;
use Doctrine\Tests\ORM\Tools\Export\Phonenumber;
use Doctrine\Tests\ORM\Tools\Export\UserListener;

$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(
    [
        'name' => 'cms_users',
        'options' => ['engine' => 'MyISAM', 'foo' => ['bar' => 'baz']],
    ]
);
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);
$metadata->addLifecycleCallback('doStuffOnPrePersist', Events::prePersist);
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', Events::prePersist);
$metadata->addLifecycleCallback('doStuffOnPostPersist', Events::postPersist);
$metadata->mapField(
    [
        'id' => true,
        'fieldName' => 'id',
        'type' => 'integer',
        'columnName' => 'id',
    ]
);
$metadata->mapField(
    [
        'fieldName' => 'name',
        'type' => 'string',
        'length' => 50,
        'unique' => true,
        'nullable' => true,
        'columnName' => 'name',
    ]
);
$metadata->mapField(
    [
        'fieldName' => 'email',
        'type' => 'string',
        'columnName' => 'user_email',
        'columnDefinition' => 'CHAR(32) NOT NULL',
    ]
);
$metadata->mapField(
    [
        'fieldName' => 'age',
        'type' => 'integer',
        'options' => ['unsigned' => true],
    ]
);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapManyToOne(
    [
        'fieldName' => 'mainGroup',
        'targetEntity' => Group::class,
    ]
);
$metadata->mapOneToOne(
    [
        'fieldName' => 'address',
        'targetEntity' => Address::class,
        'inversedBy' => 'user',
        'cascade' =>
        [0 => 'persist'],
        'mappedBy' => null,
        'joinColumns' =>
        [
            0 =>
            [
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
        'targetEntity' => Cart::class,
        'mappedBy' => 'user',
        'cascade' =>
        [0 => 'persist'],
        'inversedBy' => null,
        'orphanRemoval' => false,
        'fetch' => ClassMetadata::FETCH_EAGER,
    ]
);
$metadata->mapOneToMany(
    [
        'fieldName' => 'phonenumbers',
        'targetEntity' => Phonenumber::class,
        'cascade' =>
        [
            1 => 'persist',
            2 => 'merge',
        ],
        'mappedBy' => 'user',
        'orphanRemoval' => true,
        'fetch' => ClassMetadata::FETCH_LAZY,
        'orderBy' =>
        ['number' => 'ASC'],
    ]
);
$metadata->mapManyToMany(
    [
        'fieldName' => 'groups',
        'targetEntity' => Export\Group::class,
        'fetch' => ClassMetadata::FETCH_EXTRA_LAZY,
        'cascade' =>
        [
            0 => 'remove',
            1 => 'persist',
            2 => 'refresh',
            3 => 'merge',
            4 => 'detach',
        ],
        'mappedBy' => null,
        'joinTable' =>
        [
            'name' => 'cms_users_groups',
            'joinColumns' =>
            [
                0 =>
                [
                    'name' => 'user_id',
                    'referencedColumnName' => 'id',
                    'unique' => false,
                    'nullable' => false,
                ],
            ],
            'inverseJoinColumns' =>
            [
                0 =>
                [
                    'name' => 'group_id',
                    'referencedColumnName' => 'id',
                    'columnDefinition' => 'INT NULL',
                ],
            ],
        ],
        'orderBy' => null,
    ]
);
$metadata->addEntityListener(Events::prePersist, UserListener::class, 'customPrePersist');
$metadata->addEntityListener(Events::postPersist, UserListener::class, 'customPostPersist');
$metadata->addEntityListener(Events::prePersist, GroupListener::class, 'prePersist');
$metadata->addEntityListener(Events::postPersist, AddressListener::class, 'customPostPersist');
