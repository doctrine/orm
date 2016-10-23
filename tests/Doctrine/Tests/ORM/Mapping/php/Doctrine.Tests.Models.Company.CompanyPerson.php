<?php

use Doctrine\ORM\Mapping;
use Doctrine\ORM\Mapping\ClassMetadata;

$tableMetadata = new Mapping\TableMetadata();
$tableMetadata->setName('company_person');

/* @var $metadata ClassMetadata */
$metadata->setPrimaryTable($tableMetadata);

$metadata->addNamedNativeQuery(
    [
        'name'              => 'fetchAllWithResultClass',
        'query'             => 'SELECT id, name, discr FROM company_persons ORDER BY name',
        'resultClass'       => CompanyPerson::class,
    ]
);

$metadata->addNamedNativeQuery(
    [
        'name'              => 'fetchAllWithSqlResultSetMapping',
        'query'             => 'SELECT id, name, discr AS discriminator FROM company_persons ORDER BY name',
        'resultSetMapping'  => 'mappingFetchAll',
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
        'entityClass' => CompanyPerson::class,
        'discriminatorColumn' => 'discriminator',
        ],
    ],
    ]
);
