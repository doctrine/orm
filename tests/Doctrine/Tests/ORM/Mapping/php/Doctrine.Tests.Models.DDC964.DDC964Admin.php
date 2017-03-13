<?php

use Doctrine\ORM\Mapping;

$joinColumns = [];

$joinColumn = new Mapping\JoinColumnMetadata();

$joinColumn->setColumnName('adminaddress_id');
$joinColumn->setReferencedColumnName('id');
$joinColumn->setOnDelete('');

$joinColumns[] = $joinColumn;

$association = new Mapping\ManyToOneAssociationMetadata('address');

$association->setJoinColumns($joinColumns);

$metadata->setAssociationOverride($association);

$joinTable = new Mapping\JoinTableMetadata();
$joinTable->setName('ddc964_users_admingroups');

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName('adminuser_id');

$joinTable->addJoinColumn($joinColumn);

$joinColumn = new Mapping\JoinColumnMetadata();
$joinColumn->setColumnName('admingroup_id');

$joinTable->addInverseJoinColumn($joinColumn);

$association = new Mapping\ManyToManyAssociationMetadata('groups');

$association->setJoinTable($joinTable);

$metadata->setAssociationOverride($association);
