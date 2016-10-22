<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;
use Doctrine\DBAL\Types\Type;

$tableMetadata = new Mapping\TableMetadata();

$tableMetadata->setName('cms_users');
$tableMetadata->addOption('engine', 'MyISAM');
$tableMetadata->addOption('foo', array('bar' => 'baz'));

$metadata->setPrimaryTable($tableMetadata);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->setInheritanceType(Mapping\InheritanceType::NONE);
$metadata->setChangeTrackingPolicy(Mapping\ChangeTrackingPolicy::DEFERRED_IMPLICIT);

$metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
$metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
$metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');


$fieldMetadata = new Mapping\FieldMetadata('id');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setPrimaryKey(true);

$metadata->addProperty($fieldMetadata);


$fieldMetadata = new Mapping\FieldMetadata('name');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setLength(50);
$fieldMetadata->setColumnName('name');
$fieldMetadata->setNullable(true);
$fieldMetadata->setUnique(true);

$metadata->addProperty($fieldMetadata);


$fieldMetadata = new Mapping\FieldMetadata('email');

$fieldMetadata->setType(Type::getType('string'));
$fieldMetadata->setColumnName('user_email');
$fieldMetadata->setColumnDefinition('CHAR(32) NOT NULL');

$metadata->addProperty($fieldMetadata);


$fieldMetadata = new Mapping\FieldMetadata('age');

$fieldMetadata->setType(Type::getType('integer'));
$fieldMetadata->setOptions(['unsigned' => true]);

$metadata->addProperty($fieldMetadata);


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
    'fetch'         => Mapping\FetchMode::EAGER,
));

$metadata->mapOneToOne(array(
    'fieldName'     => 'cart',
    'targetEntity'  => 'Doctrine\Tests\ORM\Tools\Export\Cart',
    'mappedBy'      => 'user',
    'cascade'       => array('persist'),
    'inversedBy'    => NULL,
    'orphanRemoval' => false,
    'fetch'         => Mapping\FetchMode::EAGER,
));

$metadata->mapOneToMany(array(
    'fieldName'     => 'phonenumbers',
    'targetEntity'  => 'Doctrine\Tests\ORM\Tools\Export\Phonenumber',
    'cascade'       => array('persist', 'merge'),
    'mappedBy'      => 'user',
    'orphanRemoval' => true,
    'fetch'         => Mapping\FetchMode::LAZY,
    'orderBy'       => array(
        'number' => 'ASC'
    ),
));


$joinTable = new Mapping\JoinTableMetadata();

$joinTable->setName('cms_users_groups');

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("user_id");
$joinColumn->setReferencedColumnName("id");

$joinTable->addJoinColumn($joinColumn);

$inverseJoinColumns = array();

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName("group_id");
$joinColumn->setReferencedColumnName("id");
$joinColumn->setColumnDefinition("INT NULL");

$joinTable->addInverseJoinColumn($joinColumn);

$metadata->mapManyToMany(array(
    'fieldName'    => 'groups',
    'targetEntity' => 'Doctrine\Tests\ORM\Tools\Export\Group',
    'cascade'      => array('remove', 'persist', 'refresh', 'merge', 'detach'),
    'mappedBy'     => NULL,
    'orderBy'      => NULL,
    'joinTable'    => $joinTable,
    'fetch'        => Mapping\FetchMode::EXTRA_LAZY,
));
