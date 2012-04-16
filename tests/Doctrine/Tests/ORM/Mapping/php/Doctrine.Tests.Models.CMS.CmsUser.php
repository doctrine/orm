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

$metadata->addNamedNativeQuery(array (
    "name"              => "fetchMultipleJoinsEntityResults",
    "resultSetMapping"  => "mappingMultipleJoinsEntityResults",
    "query"             => "SELECT u.id AS u_id, u.name AS u_name, u.status AS u_status, a.id AS a_id, a.zip AS a_zip, a.country AS a_country, COUNT(p.phonenumber) AS numphones FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id INNER JOIN cms_phonenumbers p ON u.id = p.user_id GROUP BY u.id, u.name, u.status, u.username, a.id, a.zip, a.country ORDER BY u.username"
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

$metadata->addSqlResultSetMapping(array(
    'name'      => 'mappingMultipleJoinsEntityResults',
    'entities'  => array(array(
            'fields' => array(
                array(
                    'name'      => 'id',
                    'column'    => 'u_id',
                ),
                array(
                    'name'      => 'name',
                    'column'    => 'u_name',
                ),
                array(
                    'name'      => 'status',
                    'column'    => 'u_status',
                )
            ),
            'entityClass'           => 'Doctrine\Tests\Models\CMS\CmsUser',
            'discriminatorColumn'   => null,
        ),
        array(
            'fields' => array(
                array(
                    'name'      => 'id',
                    'column'    => 'a_id',
                ),
                array(
                    'name'      => 'zip',
                    'column'    => 'a_zip',
                ),
                array(
                    'name'      => 'country',
                    'column'    => 'a_country',
                ),
            ),
            'entityClass'           => 'Doctrine\Tests\Models\CMS\CmsAddress',
            'discriminatorColumn'   => null,
        ),
    ),
    'columns' => array(array(
            'name' => 'numphones',
        )
    )
));