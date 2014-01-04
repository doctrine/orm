<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->setInheritanceType(ClassMetadataInfo::INHERITANCE_TYPE_NONE);
$metadata->setPrimaryTable(array(
   'name' => 'cms_users',
  ));
$metadata->setChangeTrackingPolicy(ClassMetadataInfo::CHANGETRACKING_DEFERRED_IMPLICIT);
$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');
$metadata->addNamedQuery(array(
    'name'  => 'all',
    'query' => 'SELECT u FROM __CLASS__ u'
));
$metadata->mapField(array(
   'id' => true,
   'fieldName' => 'id',
   'type' => 'integer',
   'columnName' => 'id',
   'options' => array('foo' => 'bar'),
  ));
$metadata->mapField(array(
   'fieldName' => 'name',
   'type' => 'string',
   'length' => 50,
   'unique' => true,
   'nullable' => true,
   'columnName' => 'name',
   'options' => array('foo' => 'bar', 'baz' => array('key' => 'val')),
  ));
$metadata->mapField(array(
   'fieldName' => 'email',
   'type' => 'string',
   'columnName' => 'user_email',
   'columnDefinition' => 'CHAR(32) NOT NULL',
  ));
$mapping = array('fieldName' => 'version', 'type' => 'integer');
$metadata->setVersionMapping($mapping);
$metadata->mapField($mapping);
$metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);
$metadata->mapOneToOne(array(
   'fieldName' => 'address',
   'targetEntity' => 'Doctrine\\Tests\\ORM\\Mapping\\Address',
   'cascade' =>
   array(
   0 => 'remove',
   ),
   'mappedBy' => NULL,
   'inversedBy' => 'user',
   'joinColumns' =>
   array(
   0 =>
   array(
    'name' => 'address_id',
    'referencedColumnName' => 'id',
    'onDelete' => 'CASCADE',
   ),
   ),
   'orphanRemoval' => false,
  ));
$metadata->mapOneToMany(array(
   'fieldName' => 'phonenumbers',
   'targetEntity' => 'Doctrine\\Tests\\ORM\\Mapping\\Phonenumber',
   'cascade' =>
   array(
   1 => 'persist',
   ),
   'mappedBy' => 'user',
   'orphanRemoval' => true,
   'orderBy' =>
   array(
   'number' => 'ASC',
   ),
  ));
$metadata->mapManyToMany(array(
   'fieldName' => 'groups',
   'targetEntity' => 'Doctrine\\Tests\\ORM\\Mapping\\Group',
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
$metadata->table['options'] = array(
    'foo' => 'bar', 
    'baz' => array('key' => 'val')
);
$metadata->table['uniqueConstraints'] = array(
    'search_idx' => array('columns' => array('name', 'user_email')),
);
$metadata->table['indexes'] = array(
    'name_idx' => array('columns' => array('name')), 0 => array('columns' => array('user_email'))
);
$metadata->setSequenceGeneratorDefinition(array(
        'sequenceName' => 'tablename_seq',
        'allocationSize' => 100,
        'initialValue' => 1,
    ));
