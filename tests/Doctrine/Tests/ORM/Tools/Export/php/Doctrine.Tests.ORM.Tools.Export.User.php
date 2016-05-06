<?php

use Doctrine\ORM\Mapping\ClassMetadata;

$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(array(
   'name' => 'cms_users',
   'options' => array('engine' => 'MyISAM', 'foo' => array('bar' => 'baz')),
  ));
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);
$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');
$metadata->mapField(array(
   'id' => true,
   'fieldName' => 'id',
   'type' => 'integer',
   'columnName' => 'id',
  ));
$metadata->mapField(array(
   'fieldName' => 'name',
   'type' => 'string',
   'length' => 50,
   'unique' => true,
   'nullable' => true,
   'columnName' => 'name',
  ));
$metadata->mapField(array(
   'fieldName' => 'email',
   'type' => 'string',
   'columnName' => 'user_email',
   'columnDefinition' => 'CHAR(32) NOT NULL',
  ));
$metadata->mapField(array(
   'fieldName' => 'age',
   'type' => 'integer',
   'options' => array("unsigned"=>true),
  ));
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapManyToOne(array(
    'fieldName' => 'mainGroup',
    'targetEntity' => 'Doctrine\\Tests\\ORM\Tools\\Export\\Group',
));
$metadata->mapOneToOne(array(
   'fieldName' => 'address',
   'targetEntity' => 'Doctrine\\Tests\\ORM\\Tools\\Export\\Address',
   'inversedBy' => 'user',
   'cascade' =>
   array(
   0 => 'persist',
   ),
   'mappedBy' => NULL,
   'joinColumns' =>
   array(
   0 =>
   array(
    'name' => 'address_id',
    'referencedColumnName' => 'id',
    'onDelete' => 'CASCADE',
   ),
   ),
   'orphanRemoval' => true,
   'fetch' => ClassMetadata::FETCH_EAGER,
  ));
$metadata->mapOneToOne(array(
    'fieldName' => 'cart',
    'targetEntity' => 'Doctrine\\Tests\\ORM\\Tools\\Export\\Cart',
    'mappedBy' => 'user',
    'cascade' =>
        array(
            0 => 'persist',
        ),
    'inversedBy' => NULL,
    'orphanRemoval' => false,
    'fetch' => ClassMetadataInfo::FETCH_EAGER,
));
$metadata->mapOneToMany(array(
   'fieldName' => 'phonenumbers',
   'targetEntity' => 'Doctrine\\Tests\\ORM\\Tools\\Export\\Phonenumber',
   'cascade' =>
   array(
   1 => 'persist',
   2 => 'merge',
   ),
   'mappedBy' => 'user',
   'orphanRemoval' => true,
   'fetch' => ClassMetadata::FETCH_LAZY,
   'orderBy' =>
   array(
   'number' => 'ASC',
   ),
  ));
$metadata->mapManyToMany(array(
   'fieldName' => 'groups',
   'targetEntity' => 'Doctrine\\Tests\\ORM\\Tools\\Export\\Group',
   'fetch' => ClassMetadata::FETCH_EXTRA_LAZY,
   'cascade' =>
   array(
   0 => 'remove',
   1 => 'persist',
   2 => 'refresh',
   3 => 'merge',
   4 => 'detach',
   ),
   'mappedBy' => NULL,
   'joinTable' =>
   array(
   'name' => 'cms_users_groups',
   'joinColumns' =>
   array(
    0 =>
    array(
    'name' => 'user_id',
    'referencedColumnName' => 'id',
    'unique' => false,
    'nullable' => false,
    ),
   ),
   'inverseJoinColumns' =>
   array(
    0 =>
    array(
    'name' => 'group_id',
    'referencedColumnName' => 'id',
    'columnDefinition' => 'INT NULL',
    ),
   ),
   ),
   'orderBy' => NULL,
  ));
