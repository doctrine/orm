<?php

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping;

/* @var $metadata ClassMetadata */
$tableMetadata = new Mapping\TableMetadata();

$tableMetadata->setName('company_person');

$metadata->setPrimaryTable($tableMetadata);

$metadata->addNamedNativeQuery(array (
    'name'              => 'fetchAllWithResultClass',
    'query'             => 'SELECT id, name, discr FROM company_persons ORDER BY name',
    'resultClass'       => 'Doctrine\\Tests\\Models\\Company\\CompanyPerson',
));

$metadata->addNamedNativeQuery(array (
    'name'              => 'fetchAllWithSqlResultSetMapping',
    'query'             => 'SELECT id, name, discr AS discriminator FROM company_persons ORDER BY name',
    'resultSetMapping'  => 'mappingFetchAll',
));

$metadata->addSqlResultSetMapping(array (
    'name'      => 'mappingFetchAll',
    'columns'   => array(),
    'entities'  => array ( array (
        'fields' => array (
          array (
            'name'      => 'id',
            'column'    => 'id',
          ),
          array (
            'name'      => 'name',
            'column'    => 'name',
          ),
        ),
        'entityClass' => 'Doctrine\Tests\Models\Company\CompanyPerson',
        'discriminatorColumn' => 'discriminator',
      ),
    ),
));