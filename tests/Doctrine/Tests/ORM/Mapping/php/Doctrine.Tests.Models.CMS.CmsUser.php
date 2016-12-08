<?php

use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsUser;

$metadata->setPrimaryTable(
    [
   'name' => 'cms_users',
    ]
);

$metadata->addNamedNativeQuery(
    [
    'name'              => 'fetchIdAndUsernameWithResultClass',
    'query'             => 'SELECT id, username FROM cms_users WHERE username = ?',
    'resultClass'       => CmsUser::class,
    ]
);

$metadata->addNamedNativeQuery(
    [
    'name'              => 'fetchAllColumns',
    'query'             => 'SELECT * FROM cms_users WHERE username = ?',
    'resultClass'       => CmsUser::class,
    ]
);

$metadata->addNamedNativeQuery(
    [
    'name'              => 'fetchJoinedAddress',
    'query'             => 'SELECT u.id, u.name, u.status, a.id AS a_id, a.country, a.zip, a.city FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id WHERE u.username = ?',
    'resultSetMapping'  => 'mappingJoinedAddress',
    ]
);

$metadata->addNamedNativeQuery(
    [
    'name'              => 'fetchJoinedPhonenumber',
    'query'             => 'SELECT id, name, status, phonenumber AS number FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username = ?',
    'resultSetMapping'  => 'mappingJoinedPhonenumber',
    ]
);

$metadata->addNamedNativeQuery(
    [
    'name'              => 'fetchUserPhonenumberCount',
    'query'             => 'SELECT id, name, status, COUNT(phonenumber) AS numphones FROM cms_users INNER JOIN cms_phonenumbers ON id = user_id WHERE username IN (?) GROUP BY id, name, status, username ORDER BY username',
    'resultSetMapping'  => 'mappingUserPhonenumberCount',
    ]
);

$metadata->addNamedNativeQuery(
    [
    "name"              => "fetchMultipleJoinsEntityResults",
    "resultSetMapping"  => "mappingMultipleJoinsEntityResults",
    "query"             => "SELECT u.id AS u_id, u.name AS u_name, u.status AS u_status, a.id AS a_id, a.zip AS a_zip, a.country AS a_country, COUNT(p.phonenumber) AS numphones FROM cms_users u INNER JOIN cms_addresses a ON u.id = a.user_id INNER JOIN cms_phonenumbers p ON u.id = p.user_id GROUP BY u.id, u.name, u.status, u.username, a.id, a.zip, a.country ORDER BY u.username"
    ]
);

$metadata->addSqlResultSetMapping(
    [
    'name'      => 'mappingJoinedAddress',
    'columns'   => [],
    'entities'  => [
        [
        'fields'=> [
          [
            'name'      => 'id',
            'column'    => 'id',
          ],
          [
            'name'      => 'name',
            'column'    => 'name',
          ],
          [
            'name'      => 'status',
            'column'    => 'status',
          ],
          [
            'name'      => 'address.zip',
            'column'    => 'zip',
          ],
          [
            'name'      => 'address.city',
            'column'    => 'city',
          ],
          [
            'name'      => 'address.country',
            'column'    => 'country',
          ],
          [
            'name'      => 'address.id',
            'column'    => 'a_id',
          ],
        ],
        'entityClass'           => CmsUser::class,
        'discriminatorColumn'   => null
        ],
    ],
    ]
);

$metadata->addSqlResultSetMapping(
    [
    'name'      => 'mappingJoinedPhonenumber',
    'columns'   => [],
    'entities'  => [
        [
        'fields'=> [
          [
            'name'      => 'id',
            'column'    => 'id',
          ],
          [
            'name'      => 'name',
            'column'    => 'name',
          ],
          [
            'name'      => 'status',
            'column'    => 'status',
          ],
          [
            'name'      => 'phonenumbers.phonenumber',
            'column'    => 'number',
          ],
        ],
        'entityClass'   => CmsUser::class,
        'discriminatorColumn'   => null
        ],
    ],
    ]
);

$metadata->addSqlResultSetMapping(
    [
    'name'      => 'mappingUserPhonenumberCount',
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
          [
            'name'      => 'status',
            'column'    => 'status',
          ]
        ],
        'entityClass'   => CmsUser::class,
        'discriminatorColumn'   => null
      ]
    ],
    'columns' => [
          [
            'name' => 'numphones',
          ]
    ]
    ]
);

$metadata->addSqlResultSetMapping(
    [
    'name'      => 'mappingMultipleJoinsEntityResults',
    'entities'  => [
        [
            'fields' => [
                [
                    'name'      => 'id',
                    'column'    => 'u_id',
                ],
                [
                    'name'      => 'name',
                    'column'    => 'u_name',
                ],
                [
                    'name'      => 'status',
                    'column'    => 'u_status',
                ]
            ],
            'entityClass'           => CmsUser::class,
            'discriminatorColumn'   => null,
        ],
        [
            'fields' => [
                [
                    'name'      => 'id',
                    'column'    => 'a_id',
                ],
                [
                    'name'      => 'zip',
                    'column'    => 'a_zip',
                ],
                [
                    'name'      => 'country',
                    'column'    => 'a_country',
                ],
            ],
            'entityClass'           => CmsAddress::class,
            'discriminatorColumn'   => null,
        ],
    ],
    'columns' => [
        [
            'name' => 'numphones',
        ]
    ]
    ]
);
