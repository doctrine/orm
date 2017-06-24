<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Events;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsAddressListener;

$tableMetadata = new Mapping\TableMetadata();
$tableMetadata->setName('company_person');

/* @var $metadata ClassMetadata */
$metadata->setTable($tableMetadata);

$fieldMetadata = new Mapping\FieldMetadata('id');
$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('zip');
$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setLength(50);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('city');
$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setLength(50);

$metadata->addProperty($fieldMetadata);

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setReferencedColumnName("id");

$joinColumns[] = $joinColumn;

$association = new Mapping\OneToOneAssociationMetadata('user');

$association->setJoinColumns($joinColumns);
$association->setTargetEntity(\Doctrine\Tests\Models\CMS\CmsUser::class);

$metadata->addProperty($association);

$metadata->addNamedNativeQuery(
    'find-all',
    'SELECT id, country, city FROM cms_addresses',
    [
        'resultSetMapping' => 'mapping-find-all',
    ]
);

$metadata->addNamedNativeQuery(
    'find-by-id',
    'SELECT * FROM cms_addresses WHERE id = ?',
    [
        'resultClass' => CmsAddress::class,
    ]
);

$metadata->addNamedNativeQuery(
    'count',
    'SELECT COUNT(*) AS count FROM cms_addresses',
    [
        'resultSetMapping' => 'mapping-count',
    ]
);


$metadata->addSqlResultSetMapping(
    [
        'name'      => 'mapping-find-all',
        'columns'   => [],
        'entities'  => [
            [
                'fields' => [
                    [
                        'name'   => 'id',
                        'column' => 'id',
                    ],
                    [
                        'name'   => 'city',
                        'column' => 'city',
                    ],
                    [
                        'name'   => 'country',
                        'column' => 'country',
                    ],
                ],
                'entityClass' => CmsAddress::class,
            ],
        ],
    ]
);

$metadata->addSqlResultSetMapping(
    [
        'name'      => 'mapping-without-fields',
        'columns'   => [],
        'entities'  => [
            [
                'entityClass' => '__CLASS__',
                'fields' => []
            ]
        ]
    ]
);

$metadata->addSqlResultSetMapping(
    [
        'name' => 'mapping-count',
        'columns' => [
            [
                'name' => 'count',
            ],
        ]
    ]
);

$metadata->addEntityListener(Events::postPersist, CmsAddressListener::class, 'postPersist');
$metadata->addEntityListener(Events::prePersist, CmsAddressListener::class, 'prePersist');

$metadata->addEntityListener(Events::postUpdate, CmsAddressListener::class, 'postUpdate');
$metadata->addEntityListener(Events::preUpdate, CmsAddressListener::class, 'preUpdate');

$metadata->addEntityListener(Events::postRemove, CmsAddressListener::class, 'postRemove');
$metadata->addEntityListener(Events::preRemove, CmsAddressListener::class, 'preRemove');

$metadata->addEntityListener(Events::preFlush, CmsAddressListener::class, 'preFlush');
$metadata->addEntityListener(Events::postLoad, CmsAddressListener::class, 'postLoad');
