<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

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

$joinColumns = array();

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName('address_id');
$joinColumn->setReferencedColumnName('id');

$joinColumns[] = $joinColumn;

$metadata->mapManyToOne(array(
   'fieldName'      => 'address',
   'targetEntity'   => 'DDC964Address',
   'cascade'        => array('persist','merge'),
   'joinColumns'    => $joinColumns,
));

$joinColumns = array();

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("user_id");
$joinColumn->setReferencedColumnName("id");

$joinColumns[] = $joinColumn;

$inverseJoinColumns = array();

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("group_id");
$joinColumn->setReferencedColumnName("id");

$inverseJoinColumns[] = $joinColumn;

$joinTable = array(
    'name'               => 'ddc964_users_groups',
    'joinColumns'        => $joinColumns,
    'inverseJoinColumns' => $inverseJoinColumns,
);

$metadata->mapManyToMany(array(
   'fieldName'    => 'groups',
   'targetEntity' => 'DDC964Group',
   'inversedBy'   => 'users',
   'cascade'      => array('persist','merge','detach'),
   'joinTable'    => $joinTable,
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);