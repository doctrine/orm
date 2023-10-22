<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\CMS\CmsEmail;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Legacy\LegacyUser;
use Doctrine\Tests\Models\Legacy\LegacyUserReference;
use Doctrine\Tests\OrmTestCase;

/**
 * Description of ResultSetMappingTest
 */
class ResultSetMappingTest extends OrmTestCase
{
    /** @var ResultSetMapping */
    private $_rsm;

    /** @var EntityManagerInterface */
    private $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_rsm          = new ResultSetMapping();
        $this->entityManager = $this->getTestEntityManager();
    }

    /**
     * For SQL: SELECT id, status, username, name FROM cms_users
     */
    public function testBasicResultSetMapping(): void
    {
        $this->_rsm->addEntityResult(
            CmsUser::class,
            'u'
        );
        $this->_rsm->addFieldResult('u', 'id', 'id');
        $this->_rsm->addFieldResult('u', 'status', 'status');
        $this->_rsm->addFieldResult('u', 'username', 'username');
        $this->_rsm->addFieldResult('u', 'name', 'name');

        self::assertFalse($this->_rsm->isScalarResult('id'));
        self::assertFalse($this->_rsm->isScalarResult('status'));
        self::assertFalse($this->_rsm->isScalarResult('username'));
        self::assertFalse($this->_rsm->isScalarResult('name'));

        self::assertEquals($this->_rsm->getClassName('u'), CmsUser::class);
        $class = $this->_rsm->getDeclaringClass('id');
        self::assertEquals($class, CmsUser::class);

        self::assertEquals('u', $this->_rsm->getEntityAlias('id'));
        self::assertEquals('u', $this->_rsm->getEntityAlias('status'));
        self::assertEquals('u', $this->_rsm->getEntityAlias('username'));
        self::assertEquals('u', $this->_rsm->getEntityAlias('name'));

        self::assertEquals('id', $this->_rsm->getFieldName('id'));
        self::assertEquals('status', $this->_rsm->getFieldName('status'));
        self::assertEquals('username', $this->_rsm->getFieldName('username'));
        self::assertEquals('name', $this->_rsm->getFieldName('name'));
    }

    /**
     * @group DDC-1057
     *
     * Fluent interface test, not a real result set mapping
     */
    public function testFluentInterface(): void
    {
        $rms = $this->_rsm;

        $this->_rsm->addEntityResult(CmsUser::class, 'u');
        $this->_rsm->addJoinedEntityResult(CmsPhonenumber::class, 'p', 'u', 'phonenumbers');
        $this->_rsm->addFieldResult('u', 'id', 'id');
        $this->_rsm->addFieldResult('u', 'name', 'name');
        $this->_rsm->setDiscriminatorColumn('name', 'name');
        $this->_rsm->addIndexByColumn('id', 'id');
        $this->_rsm->addIndexBy('username', 'username');
        $this->_rsm->addIndexByScalar('sclr0');
        $this->_rsm->addScalarResult('sclr0', 'numPhones');
        $this->_rsm->addMetaResult('a', 'user_id', 'user_id');

        self::assertTrue($rms->hasIndexBy('id'));
        self::assertTrue($rms->isFieldResult('id'));
        self::assertTrue($rms->isFieldResult('name'));
        self::assertTrue($rms->isScalarResult('sclr0'));
        self::assertTrue($rms->isRelation('p'));
        self::assertTrue($rms->hasParentAlias('p'));
        self::assertTrue($rms->isMixedResult());
    }

    /** @group DDC-1663 */
    public function testAddNamedNativeQueryResultSetMapping(): void
    {
        $cm = new ClassMetadata(CmsUser::class);
        $cm->initializeReflection(new RuntimeReflectionService());

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
                    ],
                ],
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
                                'column' => 'user_id',
                            ],
                            [
                                'name'  => 'name',
                                'column' => 'name',
                            ],
                        ],
                    ],
                    [
                        'entityClass'   => 'CmsEmail',
                        'fields'        => [
                            [
                                'name'  => 'id',
                                'column' => 'email_id',
                            ],
                            [
                                'name'  => 'email',
                                'column' => 'email',
                            ],
                        ],
                    ],
                ],
                'columns'   => [
                    ['name' => 'scalarColumn'],
                ],
            ]
        );

        $queryMapping = $cm->getNamedNativeQuery('find-all');

        $rsm = new ResultSetMappingBuilder($this->entityManager);
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

        /** @group DDC-1663 */
    public function testAddNamedNativeQueryResultSetMappingWithoutFields(): void
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
                    ['entityClass' => '__CLASS__'],
                ],
                'columns'   => [
                    ['name' => 'scalarColumn'],
                ],
            ]
        );

        $queryMapping = $cm->getNamedNativeQuery('find-all');
        $rsm          = new ResultSetMappingBuilder($this->entityManager);

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

    /** @group DDC-1663 */
    public function testAddNamedNativeQueryResultClass(): void
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
        $rsm          = new ResultSetMappingBuilder($this->entityManager);

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

    /** @group DDC-117 */
    public function testIndexByMetadataColumn(): void
    {
        $this->_rsm->addEntityResult(LegacyUser::class, 'u');
        $this->_rsm->addJoinedEntityResult(LegacyUserReference::class, 'lu', 'u', '_references');
        $this->_rsm->addMetaResult('lu', '_source', '_source', true, 'integer');
        $this->_rsm->addMetaResult('lu', '_target', '_target', true, 'integer');
        $this->_rsm->addIndexBy('lu', '_source');

        self::assertTrue($this->_rsm->hasIndexBy('lu'));
    }
}
