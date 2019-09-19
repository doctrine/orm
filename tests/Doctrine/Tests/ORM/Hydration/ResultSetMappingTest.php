<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Legacy\LegacyUser;
use Doctrine\Tests\Models\Legacy\LegacyUserReference;

/**
 * Description of ResultSetMappingTest
 *
 * @author robo
 */
class ResultSetMappingTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var ResultSetMapping
     */
    private $_rsm;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $_em;

    protected function setUp() {
        parent::setUp();
        $this->_rsm = new ResultSetMapping;
        $this->_em = $this->_getTestEntityManager();
    }

    /**
     * For SQL: SELECT id, status, username, name FROM cms_users
     */
    public function testBasicResultSetMapping()
    {
        $this->_rsm->addEntityResult(
            CmsUser::class,
            'u'
        );
        $this->_rsm->addFieldResult('u', 'id', 'id');
        $this->_rsm->addFieldResult('u', 'status', 'status');
        $this->_rsm->addFieldResult('u', 'username', 'username');
        $this->_rsm->addFieldResult('u', 'name', 'name');

        $this->assertFalse($this->_rsm->isScalarResult('id'));
        $this->assertFalse($this->_rsm->isScalarResult('status'));
        $this->assertFalse($this->_rsm->isScalarResult('username'));
        $this->assertFalse($this->_rsm->isScalarResult('name'));

        $this->assertTrue($this->_rsm->getClassName('u') == CmsUser::class);
        $class = $this->_rsm->getDeclaringClass('id');
        $this->assertTrue($class == CmsUser::class);

        $this->assertEquals('u', $this->_rsm->getEntityAlias('id'));
        $this->assertEquals('u', $this->_rsm->getEntityAlias('status'));
        $this->assertEquals('u', $this->_rsm->getEntityAlias('username'));
        $this->assertEquals('u', $this->_rsm->getEntityAlias('name'));

        $this->assertEquals('id', $this->_rsm->getFieldName('id'));
        $this->assertEquals('status', $this->_rsm->getFieldName('status'));
        $this->assertEquals('username', $this->_rsm->getFieldName('username'));
        $this->assertEquals('name', $this->_rsm->getFieldName('name'));
    }

    /**
     * @group DDC-1057
     *
     * Fluent interface test, not a real result set mapping
     */
    public function testFluentInterface()
    {
        $rms = $this->_rsm;

        $this->_rsm->addEntityResult(CmsUser::class,'u');
        $this->_rsm->addJoinedEntityResult(CmsPhonenumber::class,'p','u','phonenumbers');
        $this->_rsm->addFieldResult('u', 'id', 'id');
        $this->_rsm->addFieldResult('u', 'name', 'name');
        $this->_rsm->setDiscriminatorColumn('name', 'name');
        $this->_rsm->addIndexByColumn('id', 'id');
        $this->_rsm->addIndexBy('username', 'username');
        $this->_rsm->addIndexByScalar('sclr0');
        $this->_rsm->addScalarResult('sclr0', 'numPhones');
        $this->_rsm->addMetaResult('a', 'user_id', 'user_id');

        $this->assertTrue($rms->hasIndexBy('id'));
        $this->assertTrue($rms->isFieldResult('id'));
        $this->assertTrue($rms->isFieldResult('name'));
        $this->assertTrue($rms->isScalarResult('sclr0'));
        $this->assertTrue($rms->isRelation('p'));
        $this->assertTrue($rms->hasParentAlias('p'));
        $this->assertTrue($rms->isMixedResult());
    }

    /**
     * @group DDC-1663
     */
    public function testAddNamedNativeQueryResultSetMapping()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        $cm->mapOneToOne(
            [
            'fieldName'     => 'email',
            'targetEntity'  => CmsEmail::class,
            'cascade'       => ['persist'],
            'inversedBy'    => 'user',
            'orphanRemoval' => false,
            'joinColumns'   => [
                [
                    'nullable' => true,
                    'referencedColumnName' => 'id',
                ]
            ]
            ]
        );

        $cm->addNamedNativeQuery(
            [
            'name'              => 'find-all',
            'query'             => 'SELECT u.id AS user_id, e.id AS email_id, u.name, e.email, u.id + e.id AS scalarColumn FROM cms_users u INNER JOIN cms_emails e ON e.id = u.email_id',
            'resultSetMapping'  => 'find-all',
            ]
        );

        $cm->addSqlResultSetMapping(
            [
            'name'      => 'find-all',
            'entities'  => [
                [
                    'entityClass'   => '__CLASS__',
                    'fields'        => [
                        [
                            'name'  => 'id',
                            'column'=> 'user_id'
                        ],
                        [
                            'name'  => 'name',
                            'column'=> 'name'
                        ]
                    ]
                ],
                [
                    'entityClass'   => 'CmsEmail',
                    'fields'        => [
                        [
                            'name'  => 'id',
                            'column'=> 'email_id'
                        ],
                        [
                            'name'  => 'email',
                            'column'=> 'email'
                        ]
                    ]
                ]
            ],
            'columns'   => [
                [
                    'name' => 'scalarColumn'
                ]
            ]
            ]
        );

        $queryMapping = $cm->getNamedNativeQuery('find-all');

        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->_em);
        $rsm->addNamedNativeQueryMapping($cm, $queryMapping);

        $this->assertEquals('scalarColumn', $rsm->getScalarAlias('scalarColumn'));

        $this->assertEquals('c0', $rsm->getEntityAlias('user_id'));
        $this->assertEquals('c0', $rsm->getEntityAlias('name'));
        $this->assertEquals(CmsUser::class, $rsm->getClassName('c0'));
        $this->assertEquals(CmsUser::class, $rsm->getDeclaringClass('name'));
        $this->assertEquals(CmsUser::class, $rsm->getDeclaringClass('user_id'));


        $this->assertEquals('c1', $rsm->getEntityAlias('email_id'));
        $this->assertEquals('c1', $rsm->getEntityAlias('email'));
        $this->assertEquals(CmsEmail::class, $rsm->getClassName('c1'));
        $this->assertEquals(CmsEmail::class, $rsm->getDeclaringClass('email'));
        $this->assertEquals(CmsEmail::class, $rsm->getDeclaringClass('email_id'));
    }

        /**
     * @group DDC-1663
     */
    public function testAddNamedNativeQueryResultSetMappingWithoutFields()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        $cm->addNamedNativeQuery(
            [
            'name'              => 'find-all',
            'query'             => 'SELECT u.id AS user_id, e.id AS email_id, u.name, e.email, u.id + e.id AS scalarColumn FROM cms_users u INNER JOIN cms_emails e ON e.id = u.email_id',
            'resultSetMapping'  => 'find-all',
            ]
        );

        $cm->addSqlResultSetMapping(
            [
            'name'      => 'find-all',
            'entities'  => [
                [
                    'entityClass'   => '__CLASS__',
                ]
            ],
            'columns'   => [
                [
                    'name' => 'scalarColumn'
                ]
            ]
            ]
        );

        $queryMapping = $cm->getNamedNativeQuery('find-all');
        $rsm          = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->_em);

        $rsm->addNamedNativeQueryMapping($cm, $queryMapping);

        $this->assertEquals('scalarColumn', $rsm->getScalarAlias('scalarColumn'));
        $this->assertEquals('c0', $rsm->getEntityAlias('id'));
        $this->assertEquals('c0', $rsm->getEntityAlias('name'));
        $this->assertEquals('c0', $rsm->getEntityAlias('status'));
        $this->assertEquals('c0', $rsm->getEntityAlias('username'));
        $this->assertEquals(CmsUser::class, $rsm->getClassName('c0'));
        $this->assertEquals(CmsUser::class, $rsm->getDeclaringClass('id'));
        $this->assertEquals(CmsUser::class, $rsm->getDeclaringClass('name'));
        $this->assertEquals(CmsUser::class, $rsm->getDeclaringClass('status'));
        $this->assertEquals(CmsUser::class, $rsm->getDeclaringClass('username'));
    }

    /**
     * @group DDC-1663
     */
    public function testAddNamedNativeQueryResultClass()
    {
        $cm = new ClassMetadata(CmsUser::class);

        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        $cm->addNamedNativeQuery(
            [
            'name'              => 'find-all',
            'resultClass'       => '__CLASS__',
            'query'             => 'SELECT * FROM cms_users',
            ]
        );

        $queryMapping = $cm->getNamedNativeQuery('find-all');
        $rsm          = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->_em);

        $rsm->addNamedNativeQueryMapping($cm, $queryMapping);

        $this->assertEquals('c0', $rsm->getEntityAlias('id'));
        $this->assertEquals('c0', $rsm->getEntityAlias('name'));
        $this->assertEquals('c0', $rsm->getEntityAlias('status'));
        $this->assertEquals('c0', $rsm->getEntityAlias('username'));
        $this->assertEquals(CmsUser::class, $rsm->getClassName('c0'));
        $this->assertEquals(CmsUser::class, $rsm->getDeclaringClass('id'));
        $this->assertEquals(CmsUser::class, $rsm->getDeclaringClass('name'));
        $this->assertEquals(CmsUser::class, $rsm->getDeclaringClass('status'));
        $this->assertEquals(CmsUser::class, $rsm->getDeclaringClass('username'));
    }

    /**
     * @group DDC-117
     */
    public function testIndexByMetadataColumn()
    {
        $this->_rsm->addEntityResult(LegacyUser::class, 'u');
        $this->_rsm->addJoinedEntityResult(LegacyUserReference::class, 'lu', 'u', '_references');
        $this->_rsm->addMetaResult('lu', '_source',  '_source', true, 'integer');
        $this->_rsm->addMetaResult('lu', '_target',  '_target', true, 'integer');
        $this->_rsm->addIndexBy('lu', '_source');

        $this->assertTrue($this->_rsm->hasIndexBy('lu'));
    }
}

