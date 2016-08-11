<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

$metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);

$metadata->setPrimaryTable(array('name' => 'cms_users'));

$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');

$metadata->addNamedQuery(array(
    'name'  => 'all',
    'query' => 'SELECT u FROM __CLASS__ u'
));

$metadata->addProperty('id', Type::getType('integer'), array(
    'id'      => true,
    'options' => array('foo' => 'bar'),
));

$metadata->addProperty('name', Type::getType('string'), array(
    'length'     => 50,
    'unique'     => true,
    'nullable'   => true,
    'columnName' => 'name',
    'options'    => array(
        'foo' => 'bar',
        'baz' => array('key' => 'val')
    ),
));

$metadata->addProperty('email', Type::getType('string'), array(
    'columnName'       => 'user_email',
    'columnDefinition' => 'CHAR(32) NOT NULL',
));

$metadata->setVersionProperty($metadata->addProperty('version', Type::getType('integer')));

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$joinColumns = array();

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName('address_id');
$joinColumn->setReferencedColumnName('id');
$joinColumn->setOnDelete('CASCADE');

$joinColumns[] = $joinColumn;

$metadata->mapOneToOne(array(
    'fieldName'     => 'address',
    'targetEntity'  => 'Doctrine\\Tests\\ORM\\Mapping\\Address',
    'cascade'       => array('remove'),
    'mappedBy'      => NULL,
    'inversedBy'    => 'user',
    'joinColumns'   => $joinColumns,
    'orphanRemoval' => false,
));

$metadata->mapOneToMany(array(
    'fieldName'     => 'phonenumbers',
    'targetEntity'  => 'Doctrine\\Tests\\ORM\\Mapping\\Phonenumber',
    'cascade'       => array('persist'),
    'mappedBy'      => 'user',
    'orphanRemoval' => true,
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
    'targetEntity' => 'Doctrine\\Tests\\ORM\\Mapping\\Group',
    'cascade'      => array('remove', 'persist', 'refresh', 'merge', 'detach'),
    'mappedBy'     => NULL,
    'joinTable'    => $joinTable,
    'orderBy'      => NULL,
));

$metadata->table['options'] = array(
    'foo' => 'bar',
    'baz' => array('key' => 'val')
);

$metadata->table['uniqueConstraints'] = array(
    'search_idx' => array('columns' => array('name', 'user_email'), 'options' => array('where' => 'name IS NOT NULL')),
);

$metadata->table['indexes'] = array(
    'name_idx' => array('columns' => array('name')), 0 => array('columns' => array('user_email'))
);

$metadata->setSequenceGeneratorDefinition(array(
        'sequenceName' => 'tablename_seq',
        'allocationSize' => 100,
        'initialValue' => 1,
    ));
