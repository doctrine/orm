<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\Legacy\LegacyUser;
use Doctrine\Tests\Models\Legacy\LegacyUserReference;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Description of ResultSetMappingTest
 */
class ResultSetMappingTest extends OrmTestCase
{
    private ResultSetMapping $_rsm;
    private EntityManagerInterface $entityManager;

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
            'u',
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
     * Fluent interface test, not a real result set mapping
     */
    #[Group('DDC-1057')]
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

    #[Group('DDC-117')]
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
