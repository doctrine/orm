<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Hydration;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataBuildingContext;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Reflection\RuntimeReflectionService;
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
    /** @var EntityManagerInterface */
    private $em;

    /** @var ResultSetMapping */
    private $rsm;

    /** @var ClassMetadataBuildingContext */
    private $metadataBuildingContext;

    protected function setUp() : void
    {
        parent::setUp();

        $this->metadataBuildingContext = new ClassMetadataBuildingContext(
            $this->createMock(ClassMetadataFactory::class),
            new RuntimeReflectionService()
        );

        $this->em  = $this->getTestEntityManager();
        $this->rsm = new ResultSetMapping();
    }

    /**
     * For SQL: SELECT id, status, username, name FROM cms_users
     */
    public function testBasicResultSetMapping() : void
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

        self::assertEquals($this->rsm->getClassName('u'), CmsUser::class);
        $class = $this->rsm->getDeclaringClass('id');
        self::assertEquals($class, CmsUser::class);

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
    public function testFluentInterface() : void
    {
        $rms = $this->rsm;

        $this->rsm->addEntityResult(CmsUser::class, 'u');
        $this->rsm->addJoinedEntityResult(CmsPhonenumber::class, 'p', 'u', 'phonenumbers');
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
     * @group DDC-117
     */
    public function testIndexByMetadataColumn() : void
    {
        $this->rsm->addEntityResult(LegacyUser::class, 'u');
        $this->rsm->addJoinedEntityResult(LegacyUserReference::class, 'lu', 'u', '_references');
        $this->rsm->addMetaResult('lu', '_source', '_source', true, Type::getType('integer'));
        $this->rsm->addMetaResult('lu', '_target', '_target', true, Type::getType('integer'));
        $this->rsm->addIndexBy('lu', '_source');

        self::assertTrue($this->rsm->hasIndexBy('lu'));
    }
}
