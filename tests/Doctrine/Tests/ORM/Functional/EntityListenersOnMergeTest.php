<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Tests\Models\Company\CompanyContractListener;
use Doctrine\Tests\Models\Company\CompanyFixContract;
use Doctrine\Tests\Models\DDC3597\DDC3597Image;
use Doctrine\Tests\Models\DDC3597\DDC3597Media;
use Doctrine\Tests\Models\DDC3597\DDC3597Root;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-1955
 * @group 5570
 * @group 6174
 */
class EntityListenersOnMergeTest extends OrmFunctionalTestCase
{
    /**
     * @var CompanyContractListener
     */
    private $listener;

    protected function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();

        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(DDC3597Root::class),
            $this->_em->getClassMetadata(DDC3597Media::class),
            $this->_em->getClassMetadata(DDC3597Image::class),
        ]);

        $this->listener = $this->_em->getConfiguration()
            ->getEntityListenerResolver()
            ->resolve(CompanyContractListener::class);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema([
            $this->_em->getClassMetadata(DDC3597Root::class),
            $this->_em->getClassMetadata(DDC3597Media::class),
            $this->_em->getClassMetadata(DDC3597Image::class),
        ]);
    }

    public function testMergeNewEntityLifecyleEventsModificationsShouldBeKept()
    {
        $imageEntity = new DDC3597Image('foobar');

        $imageEntity->setFormat('JPG');
        $imageEntity->setSize(123);
        $imageEntity->getDimension()->setWidth(300);
        $imageEntity->getDimension()->setHeight(500);

        $imageEntity = $this->_em->merge($imageEntity);

        $this->assertNotNull($imageEntity->getCreatedAt());
        $this->assertNotNull($imageEntity->getUpdatedAt());
    }

    public function testPrePersistListenersShouldBeFiredWithCorrectEntityData()
    {
        $fix = new CompanyFixContract();

        $fix->setFixPrice(2000);

        $this->listener->prePersistCalls = [];

        $fix = $this->_em->merge($fix);
        $this->_em->flush();

        $this->assertCount(1, $this->listener->prePersistCalls);

        $this->assertSame($fix, $this->listener->prePersistCalls[0][0]);

        $this->assertInstanceOf(CompanyFixContract::class, $this->listener->prePersistCalls[0][0]);
        $this->assertInstanceOf(LifecycleEventArgs::class, $this->listener->prePersistCalls[0][1]);

        $this->assertArrayHasKey('fixPrice', $this->listener->snapshots[CompanyContractListener::PRE_PERSIST][0]);
        $this->assertEquals(
            $fix->getFixPrice(),
            $this->listener->snapshots[CompanyContractListener::PRE_PERSIST][0]['fixPrice']
        );
    }
}
