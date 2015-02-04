<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->setPrimaryTable([
   'name' => 'company_person',
]);

$metadata->mapField( [
    'id'        => true,
    'fieldName' => 'id',
    'type'      => 'integer',
]);

$metadata->mapField( [
    'fieldName' => 'zip',
    'length'    => 50,
]);

$metadata->mapField( [
    'fieldName' => 'city',
    'length'    => 50,
]);

$metadata->mapOneToOne([
    'fieldName'     => 'user',
    'targetEntity'  => 'CmsUser',
    'joinColumns'   => [['referencedColumnName' => 'id']]
]);

$metadata->addNamedNativeQuery( [
    'name'              => 'find-all',
    'query'             => 'SELECT id, country, city FROM cms_addresses',
    'resultSetMapping'  => 'mapping-find-all',
]);

$metadata->addNamedNativeQuery( [
    'name'              => 'find-by-id',
    'query'             => 'SELECT * FROM cms_addresses WHERE id = ?',
    'resultClass'       => 'Doctrine\\Tests\\Models\\CMS\\CmsAddress',
]);

$metadata->addNamedNativeQuery( [
    'name'              => 'count',
    'query'             => 'SELECT COUNT(*) AS count FROM cms_addresses',
    'resultSetMapping'  => 'mapping-count',
]);


$metadata->addSqlResultSetMapping( [
    'name'      => 'mapping-find-all',
    'columns'   => [],
    'entities'  =>  [  [
        'fields' =>  [
           [
            'name'      => 'id',
            'column'    => 'id',
          ],
           [
            'name'      => 'city',
            'column'    => 'city',
          ],
           [
            'name'      => 'country',
            'column'    => 'country',
          ],
        ],
        'entityClass' => 'Doctrine\Tests\Models\CMS\CmsAddress',
      ],
    ],
]);

$metadata->addSqlResultSetMapping( [
    'name'      => 'mapping-without-fields',
    'columns'   => [],
    'entities'  => [ [
        'entityClass' => 'Doctrine\\Tests\\Models\\CMS\\CmsAddress',
        'fields' => []
      ]
    ]
]);

$metadata->addSqlResultSetMapping( [
    'name' => 'mapping-count',
    'columns' => [
         [
            'name' => 'count',
        ],
    ]
]);

$metadata->addEntityListener(\Doctrine\ORM\Events::postPersist, 'CmsAddressListener', 'postPersist');
$metadata->addEntityListener(\Doctrine\ORM\Events::prePersist, 'CmsAddressListener', 'prePersist');

$metadata->addEntityListener(\Doctrine\ORM\Events::postUpdate, 'CmsAddressListener', 'postUpdate');
$metadata->addEntityListener(\Doctrine\ORM\Events::preUpdate, 'CmsAddressListener', 'preUpdate');

$metadata->addEntityListener(\Doctrine\ORM\Events::postRemove, 'CmsAddressListener', 'postRemove');
$metadata->addEntityListener(\Doctrine\ORM\Events::preRemove, 'CmsAddressListener', 'preRemove');

$metadata->addEntityListener(\Doctrine\ORM\Events::preFlush, 'CmsAddressListener', 'preFlush');
$metadata->addEntityListener(\Doctrine\ORM\Events::postLoad, 'CmsAddressListener', 'postLoad');