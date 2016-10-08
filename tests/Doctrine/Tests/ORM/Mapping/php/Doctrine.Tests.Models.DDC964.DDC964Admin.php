<?php

use Doctrine\ORM\Mapping;

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName('adminaddress_id');
$joinColumn->setReferencedColumnName('id');
$joinColumn->setOnDelete('');

$joinColumns[] = $joinColumn;

$metadata->setAssociationOverride('address', ['joinColumns' => $joinColumns]);

$joinTable = new Mapping\JoinTableMetadata();
$joinTable->setName('ddc964_users_admingroups');

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName('adminuser_id');

$joinTable->addJoinColumn($joinColumn);

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName('admingroup_id');

$joinTable->addInverseJoinColumn($joinColumn);

$metadata->setAssociationOverride('groups', ['joinTable' => $joinTable]);
