<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->setPrimaryTable(array(
   'name' => 'company_person',
));


$metadata->addNamedNativeQuery(array (
    'name'              => 'find-all',
    'query'             => 'SELECT id, country, city FROM cms_addresses',
    'resultSetMapping'  => 'mapping-find-all',
));

$metadata->addNamedNativeQuery(array (
    'name'              => 'find-by-id',
    'query'             => 'SELECT * FROM cms_addresses WHERE id = ?',
    'resultClass'       => 'Doctrine\\Tests\\Models\\CMS\\CmsAddress',
));

$metadata->addNamedNativeQuery(array (
    'name'              => 'count',
    'query'             => 'SELECT COUNT(*) AS count FROM cms_addresses',
    'resultSetMapping'  => 'mapping-count',
));


$metadata->addSqlResultSetMapping(array (
    'name'      => 'mapping-find-all',
    'columns'   => array(),
    'entities'  => array ( array (
        'fields' => array (
          array (
            'name'      => 'id',
            'column'    => 'id',
          ),
          array (
            'name'      => 'city',
            'column'    => 'city',
          ),
          array (
            'name'      => 'country',
            'column'    => 'country',
          ),
        ),
        'entityClass' => 'Doctrine\Tests\Models\CMS\CmsAddress',
      ),
    ),
));

$metadata->addSqlResultSetMapping(array (
    'name'      => 'mapping-without-fields',
    'columns'   => array(),
    'entities'  => array(array (
        'entityClass' => 'Doctrine\\Tests\\Models\\CMS\\CmsAddress',
        'fields' => array()
      )
    )
));

$metadata->addSqlResultSetMapping(array (
    'name' => 'mapping-count',
    'columns' =>array (
        array (
            'name' => 'count',
        ),
    )
));