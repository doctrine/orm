<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Mapping\ClassMetadata;

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
    private $_em;

    /**
     * @var ResultSetMapping
     */
    private $_rsm;

    protected function setUp()
    {
        parent::setUp();

        $this->_em  = $this->_getTestEntityManager();
        $this->_rsm = new ResultSetMapping;
    }

    /**
     * For SQL: SELECT id, status, username, name FROM cms_users
     */
    public function testBasicResultSetMapping()
    {
        $this->_rsm->addEntityResult(
            'Doctrine\Tests\Models\CMS\CmsUser',
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

        self::assertTrue($this->_rsm->getClassName('u') == 'Doctrine\Tests\Models\CMS\CmsUser');
        $class = $this->_rsm->getDeclaringClass('id');
        self::assertTrue($class == 'Doctrine\Tests\Models\CMS\CmsUser');

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
    public function testFluentInterface()
    {
        $rms = $this->_rsm;

        $this->_rsm->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser','u');
        $this->_rsm->addJoinedEntityResult('Doctrine\Tests\Models\CMS\CmsPhonenumber','p','u','phonenumbers');
        $this->_rsm->addFieldResult('u', 'id', 'id');
        $this->_rsm->addFieldResult('u', 'name', 'name');
        $this->_rsm->setDiscriminatorColumn('name', 'name');
        $this->_rsm->addIndexByColumn('id', 'id');
        $this->_rsm->addIndexBy('username', 'username');
        $this->_rsm->addIndexByScalar('sclr0');
        $this->_rsm->addScalarResult('sclr0', 'numPhones', Type::getType('integer'));
        $this->_rsm->addMetaResult('a', 'user_id', 'user_id', false, Type::getType('integer'));

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
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->mapOneToOne(array(
            'fieldName'     => 'email',
            'targetEntity'  => 'Doctrine\Tests\Models\CMS\CmsEmail',
            'cascade'       => array('persist'),
            'inversedBy'    => 'user',
            'orphanRemoval' => false,
            'joinColumns'   => array(
                array(
                    'nullable' => true,
                    'referencedColumnName' => 'id',
                    'onDelete' => null,
                )
            )
        ));

        $cm->addNamedNativeQuery(array(
            'name'              => 'find-all',
            'query'             => 'SELECT u.id AS user_id, e.id AS email_id, u.name, e.email, u.id + e.id AS scalarColumn FROM cms_users u INNER JOIN cms_emails e ON e.id = u.email_id',
            'resultSetMapping'  => 'find-all',
        ));

        $cm->addSqlResultSetMapping(array(
            'name'      => 'find-all',
            'entities'  => array(
                array(
                    'entityClass'   => '__CLASS__',
                    'fields'        => array(
                        array(
                            'name'  => 'id',
                            'column'=> 'user_id'
                        ),
                        array(
                            'name'  => 'name',
                            'column'=> 'name'
                        )
                    )
                ),
                array(
                    'entityClass'   => 'CmsEmail',
                    'fields'        => array(
                        array(
                            'name'  => 'id',
                            'column'=> 'email_id'
                        ),
                        array(
                            'name'  => 'email',
                            'column'=> 'email'
                        )
                    )
                )
            ),
            'columns'   => array(
                array(
                    'name' => 'scalarColumn'
                )
            )
        ));

        $queryMapping = $cm->getNamedNativeQuery('find-all');

        $rsm = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->_em);
        $rsm->addNamedNativeQueryMapping($cm, $queryMapping);

        self::assertEquals('scalarColumn', $rsm->getScalarAlias('scalarColumn'));

        self::assertEquals('c0', $rsm->getEntityAlias('user_id'));
        self::assertEquals('c0', $rsm->getEntityAlias('name'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getClassName('c0'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getDeclaringClass('name'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getDeclaringClass('user_id'));


        self::assertEquals('c1', $rsm->getEntityAlias('email_id'));
        self::assertEquals('c1', $rsm->getEntityAlias('email'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsEmail', $rsm->getClassName('c1'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsEmail', $rsm->getDeclaringClass('email'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsEmail', $rsm->getDeclaringClass('email_id'));
    }

        /**
     * @group DDC-1663
     */
    public function testAddNamedNativeQueryResultSetMappingWithoutFields()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedNativeQuery(array(
            'name'              => 'find-all',
            'query'             => 'SELECT u.id AS user_id, e.id AS email_id, u.name, e.email, u.id + e.id AS scalarColumn FROM cms_users u INNER JOIN cms_emails e ON e.id = u.email_id',
            'resultSetMapping'  => 'find-all',
        ));

        $cm->addSqlResultSetMapping(array(
            'name'      => 'find-all',
            'entities'  => array(
                array(
                    'entityClass'   => '__CLASS__',
                )
            ),
            'columns'   => array(
                array(
                    'name' => 'scalarColumn'
                )
            )
        ));

        $queryMapping = $cm->getNamedNativeQuery('find-all');
        $rsm          = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->_em);

        $rsm->addNamedNativeQueryMapping($cm, $queryMapping);

        self::assertEquals('scalarColumn', $rsm->getScalarAlias('scalarColumn'));
        self::assertEquals('c0', $rsm->getEntityAlias('id'));
        self::assertEquals('c0', $rsm->getEntityAlias('name'));
        self::assertEquals('c0', $rsm->getEntityAlias('status'));
        self::assertEquals('c0', $rsm->getEntityAlias('username'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getClassName('c0'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getDeclaringClass('id'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getDeclaringClass('name'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getDeclaringClass('status'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getDeclaringClass('username'));
    }

    /**
     * @group DDC-1663
     */
    public function testAddNamedNativeQueryResultClass()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $cm->initializeReflection(new RuntimeReflectionService());

        $cm->addNamedNativeQuery(array(
            'name'              => 'find-all',
            'resultClass'       => '__CLASS__',
            'query'             => 'SELECT * FROM cms_users',
        ));

        $queryMapping = $cm->getNamedNativeQuery('find-all');
        $rsm          = new \Doctrine\ORM\Query\ResultSetMappingBuilder($this->_em);

        $rsm->addNamedNativeQueryMapping($cm, $queryMapping);

        self::assertEquals('c0', $rsm->getEntityAlias('id'));
        self::assertEquals('c0', $rsm->getEntityAlias('name'));
        self::assertEquals('c0', $rsm->getEntityAlias('status'));
        self::assertEquals('c0', $rsm->getEntityAlias('username'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getClassName('c0'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getDeclaringClass('id'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getDeclaringClass('name'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getDeclaringClass('status'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $rsm->getDeclaringClass('username'));
    }
    /**
     * @group DDC-117
     */
    public function testIndexByMetadataColumn()
    {
        $this->_rsm->addEntityResult('Doctrine\Tests\Models\Legacy\LegacyUser', 'u');
        $this->_rsm->addJoinedEntityResult('Doctrine\Tests\Models\LegacyUserReference', 'lu', 'u', '_references');
        $this->_rsm->addMetaResult('lu', '_source',  '_source', true, Type::getType('integer'));
        $this->_rsm->addMetaResult('lu', '_target',  '_target', true, Type::getType('integer'));
        $this->_rsm->addIndexBy('lu', '_source');

        self::assertTrue($this->_rsm->hasIndexBy('lu'));
    }
}

