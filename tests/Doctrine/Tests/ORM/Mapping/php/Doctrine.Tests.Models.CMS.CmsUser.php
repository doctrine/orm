<?php

use Doctrine\ORM\Mapping\ClassMetadataInfo;

$metadata->setPrimaryTable(array(
   'name' => 'cms_users',
));

$metadata->addNamedNativeQuery(array (
    'name'              => 'fetchIdAndUsernameWithResultClass',
    'query'             => 'SELECT id, username FROM cms_users WHERE username = ?',
    'resultClass'       => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
));

$metadata->addNamedNativeQuery(array (
    'name'              => 'fetchAllColumns',
    'query'             => 'SELECT * FROM cms_users WHERE username = ?',
    'resultClass'       => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
));

$metadata->addNamedNativeQuery(array (
    'name'              => 'fetchJoinedAddress',
    'query'             => 'SELECT u.id, u.name, u.status, a.id AS a_id, a.country, a.zip, a.city FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?',
    'resultSetMapping'  => 'mappingJoinedAddress',
));

$metadata->addNamedNativeQuery(array (
    'name'              => 'fetchJoinedPhonenumber',
    'query'             => 'SELECT id, name, status, phonenumber AS number FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username = ?',
    'resultSetMapping'  => 'mappingJoinedPhonenumber',
));

$metadata->addNamedNativeQuery(array (
    'name'              => 'fetchUserPhonenumberCount',
    'query'             => 'SELECT id, name, status, COUNT(phonenumber) AS numphones FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username IN (?) GROUP BY id, name, status, username ORDER BY username',
    'resultSetMapping'  => 'mappingUserPhonenumberCount',
));

$metadata->addSqlResultSetMapping(array (
    'name'      => 'mappingJoinedAddress',
    'columns'   => array(),
    'entities'  => array(array (
        'fields'=> array (
          array (
            'name'      => 'id',
            'column'    => 'id',
          ),
          array (
            'name'      => 'name',
            'column'    => 'name',
          ),
          array (
            'name'      => 'status',
            'column'    => 'status',
          ),
          array (
            'name'      => 'address.zip',
            'column'    => 'zip',
          ),
          array (
            'name'      => 'address.city',
            'column'    => 'city',
          ),
          array (
            'name'      => 'address.country',
            'column'    => 'country',
          ),
          array (
            'name'      => 'address.id',
            'column'    => 'a_id',
          ),
        ),
        'entityClass'           => 'Doctrine\Tests\Models\CMS\CmsUser',
        'discriminatorColumn'   => null
      ),
    ),
));

$metadata->addSqlResultSetMapping(array (
    'name'      => 'mappingJoinedPhonenumber',
    'columns'   => array(),
    'entities'  => array(array(
        'fields'=> array (
          array (
            'name'      => 'id',
            'column'    => 'id',
          ),
          array (
            'name'      => 'name',
            'column'    => 'name',
          ),
          array (
            'name'      => 'status',
            'column'    => 'status',
          ),
          array (
            'name'      => 'phonenumbers.phonenumber',
            'column'    => 'number',
          ),
        ),
        'entityClass'   => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
        'discriminatorColumn'   => null
      ),
    ),
));

$metadata->addSqlResultSetMapping(array (
    'name'      => 'mappingUserPhonenumberCount',
    'columns'   => array(),
    'entities'  => array (
      array(
        'fields' => array (
          array (
            'name'      => 'id',
            'column'    => 'id',
          ),
          array (
            'name'      => 'name',
            'column'    => 'name',
          ),
          array (
            'name'      => 'status',
            'column'    => 'status',
          )
        ),
        'entityClass'   => 'Doctrine\Tests\Models\CMS\CmsUser',
        'discriminatorColumn'   => null
      )
    ),
    'columns' => array (
          array (
            'name' => 'numphones',
          )
    )
));