<?php

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var $metadata ClassMetadata */
$metadata->addProperty(
    'id',
    Type::getType('integer'),
    [
       'id'         => true,
       'columnName' => 'user_id',
    ]
);

$metadata->addProperty(
    'name',
    Type::getType('string'),
    [
        'columnName'=> 'user_name',
        'nullable'  => true,
        'unique'    => false,
        'length'    => 250,
    ]
);

$metadata->mapManyToOne(
    [
       'fieldName'    => 'address',
       'targetEntity' => 'DDC964Address',
       'cascade'      => ['persist','merge'],
       'joinColumn'   => ['name'=>'address_id', 'referencedColumnMame'=>'id'],
    ]
);

$metadata->mapManyToMany(
    [
       'fieldName'    => 'groups',
       'targetEntity' => 'DDC964Group',
       'inversedBy'   => 'users',
       'cascade'      => ['persist','merge','detach'],
       'joinTable'    => [
           'name'        => 'ddc964_users_groups',
           'joinColumns' => [
               [
                   'name'                 => 'user_id',
                   'referencedColumnName' =>'id',
                   'onDelete' => null,
               ]
           ],
           'inverseJoinColumns' => [
               [
                   'name'=>'group_id',
                   'referencedColumnName'=>'id',
                   'onDelete' => null,
               ]
           ],
       ]
    ]
);

$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
