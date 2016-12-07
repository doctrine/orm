<?php

$metadata->setPrimaryTable(
    [
   'name' => 'company_person',
    ]
);

$metadata->addNamedNativeQuery(
    [
    'name'              => 'fetchAllWithResultClass',
    'query'             => 'SELECT id, name, discr FROM company_persons ORDER BY name',
    'resultClass'       => 'Doctrine\\Tests\\Models\\Company\\CompanyPerson',
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
        'entityClass' => 'Doctrine\Tests\Models\Company\CompanyPerson',
        'discriminatorColumn' => 'discriminator',
        ],
    ],
    ]
);
