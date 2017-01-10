<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
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
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var ResultSetMapping
     */
    private $rsm;

    protected function setUp()
    {
        parent::setUp();

        $this->em  = $this->getTestEntityManager();
        $this->rsm = new ResultSetMapping;
    }

    /**
     * For SQL: SELECT id, status, username, name FROM cms_users
     */
    public function testBasicResultSetMapping()
    {
        $this->rsm->addEntityResult(
            CmsUser::class,
            'u'
        );
        $this->rsm->addFieldResult('u', 'id', 'id');
        $this->rsm->addFieldResult('u', 'status', 'status');
        $this->rsm->addFieldResult('u', 'username', 'username');
        $this->rsm->addFieldResult('u', 'name', 'name');

        self::assertFalse($this->rsm->isScalarResult('id'));
        self::assertFalse($this->rsm->isScalarResult('status'));
        self::assertFalse($this->rsm->isScalarResult('username'));
        self::assertFalse($this->rsm->isScalarResult('name'));

        self::assertTrue($this->rsm->getClassName('u') == CmsUser::class);
        $class = $this->rsm->getDeclaringClass('id');
        self::assertTrue($class == CmsUser::class);

        self::assertEquals('u', $this->rsm->getEntityAlias('id'));
        self::assertEquals('u', $this->rsm->getEntityAlias('status'));
        self::assertEquals('u', $this->rsm->getEntityAlias('username'));
        self::assertEquals('u', $this->rsm->getEntityAlias('name'));

        self::assertEquals('id', $this->rsm->getFieldName('id'));
        self::assertEquals('status', $this->rsm->getFieldName('status'));
        self::assertEquals('username', $this->rsm->getFieldName('username'));
        self::assertEquals('name', $this->rsm->getFieldName('name'));
    }

    /**
     * @group DDC-1057
     *
     * Fluent interface test, not a real result set mapping
     */
    public function testFluentInterface()
    {
        $rms = $this->rsm;

        $this->rsm->addEntityResult(CmsUser::class,'u');
        $this->rsm->addJoinedEntityResult(CmsPhonenumber::class,'p','u','phonenumbers');
        $this->rsm->addFieldResult('u', 'id', 'id');
        $this->rsm->addFieldResult('u', 'name', 'name');
        $this->rsm->setDiscriminatorColumn('name', 'name');
        $this->rsm->addIndexByColumn('id', 'id');
        $this->rsm->addIndexBy('username', 'username');
        $this->rsm->addIndexByScalar('sclr0');
        $this->rsm->addScalarResult('sclr0', 'numPhones', Type::getType('integer'));
        $this->rsm->addMetaResult('a', 'user_id', 'user_id', false, Type::getType('integer'));

        self::assertTrue($rms->hasIndexBy('id'));
        self::assertTrue($rms->isFieldResult('id'));
        self::assertTrue($rms->isFieldResult('name'));
        self::assertTrue($rms->isScalarResult('sclr0'));
        self::assertTrue($rms->isRelation('p'));
        self::assertTrue($rms->hasParentAlias('p'));
        self::assertTrue($rms->isMixedResult());
    }

    /**
     * @group DDC-1663
     */
    public function testAddNamedNativeQueryResultSetMapping()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

        $joinColumns = [];

        $joinColumn = new JoinColumnMetadata();
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setNullable(true);

        $joinColumns[] = $joinColumn;

        $cm->mapOneToOne(
            [
                'fieldName'     => 'email',
                'targetEntity'  => CmsEmail::class,
                'cascade'       => ['persist'],
                'inversedBy'    => 'user',
                'orphanRemoval' => false,
                'joinColumns'   => $joinColumns,
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

        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->em);
        $rsm->addNamedNativeQueryMapping($cm, $queryMapping);

        self::assertEquals('scalarColumn', $rsm->getScalarAlias('scalarColumn'));

        self::assertEquals('c0', $rsm->getEntityAlias('user_id'));
        self::assertEquals('c0', $rsm->getEntityAlias('name'));
        self::assertEquals(CmsUser::class, $rsm->getClassName('c0'));
        self::assertEquals(CmsUser::class, $rsm->getDeclaringClass('name'));
        self::assertEquals(CmsUser::class, $rsm->getDeclaringClass('user_id'));


        self::assertEquals('c1', $rsm->getEntityAlias('email_id'));
        self::assertEquals('c1', $rsm->getEntityAlias('email'));
        self::assertEquals(CmsEmail::class, $rsm->getClassName('c1'));
        self::assertEquals(CmsEmail::class, $rsm->getDeclaringClass('email'));
        self::assertEquals(CmsEmail::class, $rsm->getDeclaringClass('email_id'));
    }

        /**
     * @group DDC-1663
     */
    public function testAddNamedNativeQueryResultSetMappingWithoutFields()
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

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
        $rsm          = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->em);

        $rsm->addNamedNativeQueryMapping($cm, $queryMapping);

        self::assertEquals('scalarColumn', $rsm->getScalarAlias('scalarColumn'));
        self::assertEquals('c0', $rsm->getEntityAlias('id'));
        self::assertEquals('c0', $rsm->getEntityAlias('name'));
        self::assertEquals('c0', $rsm->getEntityAlias('status'));
        self::assertEquals('c0', $rsm->getEntityAlias('username'));
        self::assertEquals(CmsUser::class, $rsm->getClassName('c0'));
        self::assertEquals(CmsUser::class, $rsm->getDeclaringClass('id'));
        self::assertEquals(CmsUser::class, $rsm->getDeclaringClass('name'));
        self::assertEquals(CmsUser::class, $rsm->getDeclaringClass('status'));
        self::assertEquals(CmsUser::class, $rsm->getDeclaringClass('username'));
    }

    /**
     * @group DDC-1663
     */
    public function testAddNamedNativeQueryResultClass()
    {
        $cm = new ClassMetadata(CmsUser::class);

        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedNativeQuery(
            [
            'name'              => 'find-all',
            'resultClass'       => '__CLASS__',
            'query'             => 'SELECT * FROM cms_users',
            ]
        );

        $queryMapping = $cm->getNamedNativeQuery('find-all');
        $rsm          = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->em);

        $rsm->addNamedNativeQueryMapping($cm, $queryMapping);

        self::assertEquals('c0', $rsm->getEntityAlias('id'));
        self::assertEquals('c0', $rsm->getEntityAlias('name'));
        self::assertEquals('c0', $rsm->getEntityAlias('status'));
        self::assertEquals('c0', $rsm->getEntityAlias('username'));
        self::assertEquals(CmsUser::class, $rsm->getClassName('c0'));
        self::assertEquals(CmsUser::class, $rsm->getDeclaringClass('id'));
        self::assertEquals(CmsUser::class, $rsm->getDeclaringClass('name'));
        self::assertEquals(CmsUser::class, $rsm->getDeclaringClass('status'));
        self::assertEquals(CmsUser::class, $rsm->getDeclaringClass('username'));
    }
    /**
     * @group DDC-117
     */
    public function testIndexByMetadataColumn()
    {
        $this->rsm->addEntityResult(LegacyUser::class, 'u');
        $this->rsm->addJoinedEntityResult(LegacyUserReference::class, 'lu', 'u', '_references');
        $this->rsm->addMetaResult('lu', '_source',  '_source', true, Type::getType('integer'));
        $this->rsm->addMetaResult('lu', '_target',  '_target', true, Type::getType('integer'));
        $this->rsm->addIndexBy('lu', '_source');

        self::assertTrue($this->rsm->hasIndexBy('lu'));
    }
}

