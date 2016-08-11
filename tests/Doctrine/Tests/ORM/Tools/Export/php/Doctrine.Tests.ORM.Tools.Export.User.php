<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);

$metadata->setPrimaryTable(array(
   'name' => 'cms_users',
   'options' => array(
       'engine' => 'MyISAM',
       'foo'    => array('bar' => 'baz')
   ),
));
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');

$metadata->addProperty('id', Type::getType('integer'), array(
   'id' => true,
));

$metadata->addProperty('name', Type::getType('string'), array(
   'length'     => 50,
   'unique'     => true,
   'nullable'   => true,
   'columnName' => 'name',
));

$metadata->addProperty('email', Type::getType('string'), array(
   'columnName'       => 'user_email',
   'columnDefinition' => 'CHAR(32) NOT NULL',
));

$metadata->addProperty('age', Type::getType('integer'), array(
   'options' => array('unsigned' => true),
));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$metadata->mapManyToOne(array(
    'fieldName'    => 'mainGroup',
    'targetEntity' => 'Doctrine\Tests\ORM\Tools\Export\Group',
));

$joinColumns = array();

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("address_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setOnDelete("CASCADE");

$joinColumns[] = $joinColumn;

$metadata->mapOneToOne(array(
    'fieldName'     => 'address',
    'targetEntity'  => 'Doctrine\Tests\ORM\Tools\Export\Address',
    'inversedBy'    => 'user',
    'cascade'       => array('persist'),
    'mappedBy'      => NULL,
    'joinColumns'   => $joinColumns,
    'orphanRemoval' => true,
    'fetch'         => ClassMetadata::FETCH_EAGER,
));

$metadata->mapOneToOne(array(
    'fieldName'     => 'cart',
    'targetEntity'  => 'Doctrine\Tests\ORM\Tools\Export\Cart',
    'mappedBy'      => 'user',
    'cascade'       => array('persist'),
    'inversedBy'    => NULL,
    'orphanRemoval' => false,
    'fetch'         => ClassMetadata::FETCH_EAGER,
));

$metadata->mapOneToMany(array(
    'fieldName'     => 'phonenumbers',
    'targetEntity'  => 'Doctrine\Tests\ORM\Tools\Export\Phonenumber',
    'cascade'       => array('persist', 'merge'),
    'mappedBy'      => 'user',
    'orphanRemoval' => true,
    'fetch'         => ClassMetadata::FETCH_LAZY,
    'orderBy'       => array(
        'number' => 'ASC'
    ),
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
$joinColumn->setColumnDefinition("INT NULL");

$inverseJoinColumns[] = $joinColumn;

$joinTable = array(
    'name'               => 'cms_users_groups',
    'joinColumns'        => $joinColumns,
    'inverseJoinColumns' => $inverseJoinColumns,
);

$metadata->mapManyToMany(array(
    'fieldName'    => 'groups',
    'targetEntity' => 'Doctrine\Tests\ORM\Tools\Export\Group',
    'cascade'      => array('remove', 'persist', 'refresh', 'merge', 'detach'),
    'mappedBy'     => NULL,
    'orderBy'      => NULL,
    'joinTable'    => $joinTable,
    'fetch'        => ClassMetadata::FETCH_EXTRA_LAZY,
));
