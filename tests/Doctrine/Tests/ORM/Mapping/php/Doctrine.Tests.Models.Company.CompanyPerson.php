<?php

use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

$tableMetadata = new Mapping\TableMetadata();
$tableMetadata->setName('company_person');

/* @var $metadata ClassMetadata */
$metadata->setTable($tableMetadata);

$metadata->addNamedNativeQuery(
    'fetchAllWithResultClass',
    'SELECT id, name, discr FROM company_persons ORDER BY name',
    [
        'resultClass' => CompanyPerson::class,
    ]
);

$metadata->addNamedNativeQuery(
    'fetchAllWithSqlResultSetMapping',
    'SELECT id, name, discr AS discriminator FROM company_persons ORDER BY name',
    [
        'resultSetMapping' => 'mappingFetchAll',
    ]
);

$metadata->addSqlResultSetMapping(
    [
    'name'      => 'mappingFetchAll',
    'columns'   => [],
    'entities'  => [
        [
        'fields' => [
          [
            'name'      => 'id',
            'column'    => 'id',
          ],
          [
            'name'      => 'name',
            'column'    => 'name',
          ],
        ],
        'entityClass' => '__CLASS__',
        'discriminatorColumn' => 'discriminator',
        ],
    ],
    ]
);
