<?php

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Mapping\ClassMetadata;

require_once __DIR__ . '/../../TestInit.php';

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
            'Doctrine\Tests\Models\CMS\CmsUser',
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

        $this->assertTrue($this->_rsm->getClassName('u') == 'Doctrine\Tests\Models\CMS\CmsUser');
        $class = $this->_rsm->getDeclaringClass('id');
        $this->assertTrue($class == 'Doctrine\Tests\Models\CMS\CmsUser');

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

        $rms->addEntityResult('Doctrine\Tests\Models\CMS\CmsUser','u')
            ->addJoinedEntityResult('Doctrine\Tests\Models\CMS\CmsPhonenumber','p','u','phonenumbers')
            ->addFieldResult('u', 'id', 'id')
            ->addFieldResult('u', 'name', 'name')
            ->setDiscriminatorColumn('name', 'name')
            ->addIndexByColumn('id', 'id')
            ->addIndexBy('username', 'username')
            ->addIndexByScalar('sclr0')
            ->addScalarResult('sclr0', 'numPhones')
            ->addMetaResult('a', 'user_id', 'user_id');


        $this->assertTrue($rms->hasIndexBy('id'));
        $this->assertTrue($rms->isFieldResult('id'));
        $this->assertTrue($rms->isFieldResult('name'));
        $this->assertTrue($rms->isScalarResult('sclr0'));
        $this->assertTrue($rms->isRelation('p'));
        $this->assertTrue($rms->hasParentAlias('p'));
        $this->assertTrue($rms->isMixedResult());
    }
}

