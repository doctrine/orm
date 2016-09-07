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

$metadata->mapManyToOne(
    [
       'fieldName'    => 'address',
       'targetEntity' => 'DDC964Address',
       'cascade'      => ['persist','merge'],
       'joinColumns'  => $joinColumns,
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

$inverseJoinColumns[] = $joinColumn;

$joinTable = [
    'name'               => 'ddc964_users_groups',
    'joinColumns'        => $joinColumns,
    'inverseJoinColumns' => $inverseJoinColumns,
];

$metadata->mapManyToMany(
    [
       'fieldName'    => 'groups',
       'targetEntity' => 'DDC964Group',
       'inversedBy'   => 'users',
       'cascade'      => ['persist','merge','detach'],
       'joinTable'    => $joinTable,
    ]
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
