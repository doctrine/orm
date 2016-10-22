<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

/* @var $metadata ClassMetadata */
$tableMetadata = new Mapping\TableMetadata();

$tableMetadata->setName('cms_users');
$tableMetadata->addIndex(array(
    'name'    => 'name_idx',
    'columns' => array('name'),
    'unique'  => false,
    'options' => [],
    'flags'   => [],
));
$tableMetadata->addIndex(array(
    'name'    => null,
    'columns' => array('user_email'),
    'unique'  => false,
    'options' => [],
    'flags'   => [],
));
$tableMetadata->addUniqueConstraint(array(
    'name'    => 'search_idx',
    'columns' => array('name', 'user_email'),
    'options' => [],
    'flags'   => [],
));
$tableMetadata->addOption('foo', 'bar');
$tableMetadata->addOption('baz', array('key' => 'val'));

$metadata->setPrimaryTable($tableMetadata);
$metadata->setInheritanceType(Mapping\InheritanceType::NONE);
$metadata->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_IMPLICIT);

$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');

$metadata->addNamedQuery(array(
    'name'  => 'all',
    'query' => 'SELECT u FROM __CLASS__ u'
));

$metadata->setGeneratorDefinition(array(
    'sequenceName'   => 'tablename_seq',
    'allocationSize' => 100,
));

$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);
$fieldMetadata->setOptions(['foo' => 'bar']);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('name');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setLength(50);
$fieldMetadata->setColumnName('name');
$fieldMetadata->setNullable(true);
$fieldMetadata->setUnique(true);
$fieldMetadata->setOptions([
    'foo' => 'bar',
    'baz' => [
        'key' => 'val',
    ],
]);

$metadata->addProperty($fieldMetadata);

$fieldMetadata = new Mapping\FieldMetadata('email');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setColumnName('user_email');
$fieldMetadata->setColumnDefinition('CHAR(32) NOT NULL');

$metadata->addProperty($fieldMetadata);

$versionFieldMetadata = new Mapping\VersionFieldMetadata('version');

$versionFieldMetadata->setType(Type::getType('integer'));

$metadata->addProperty($versionFieldMetadata);
$metadata->setVersionProperty($versionFieldMetadata);

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


$joinTable = new Mapping\JoinTableMetadata();

$joinTable->setName('cms_users_groups');

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("user_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setNullable(false);
$joinColumn->setUnique(false);

$joinTable->addJoinColumn($joinColumn);

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("group_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setColumnDefinition("INT NULL");

$joinTable->addInverseJoinColumn($joinColumn);

$metadata->mapManyToMany(array(
    'fieldName'    => 'groups',
    'targetEntity' => 'Doctrine\\Tests\\ORM\\Mapping\\Group',
    'cascade'      => array('remove', 'persist', 'refresh', 'merge', 'detach'),
    'mappedBy'     => NULL,
    'joinTable'    => $joinTable,
    'orderBy'      => NULL,
));