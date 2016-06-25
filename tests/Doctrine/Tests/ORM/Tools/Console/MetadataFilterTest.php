<?php

namespace Doctrine\Tests\ORM\Tools\Console;

use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;

/**
 * Tests for {@see \Doctrine\ORM\Tools\Console\MetadataFilter}
 *
 * @covers \Doctrine\ORM\Tools\Console\MetadataFilter
 */
class MetadataFilterTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var DisconnectedClassMetadataFactory
     */
    private $cmf;

    protected function setUp()
    {
        parent::setUp();

        $driver = $this->createAnnotationDriver();
        $em     = $this->_getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($driver);

        $this->cmf = new DisconnectedClassMetadataFactory();
        $this->cmf->setEntityManager($em);
    }

    public function testFilterWithEmptyArray()
    {
        $originalMetadatas = array(
            $metadataAaa = $this->cmf->getMetadataFor(MetadataFilterTestEntityAaa::CLASSNAME),
            $metadataBbb = $this->cmf->getMetadataFor(MetadataFilterTestEntityBbb::CLASSNAME),
        );

        $metadatas = $originalMetadatas;
        $metadatas = MetadataFilter::filter($metadatas, array());

        $this->assertContains($metadataAaa, $metadatas);
        $this->assertContains($metadataBbb, $metadatas);
        $this->assertCount(count($originalMetadatas), $metadatas);
    }

    public function testFilterWithString()
    {
        $originalMetadatas = array(
            $metadataAaa = $this->cmf->getMetadataFor(MetadataFilterTestEntityAaa::CLASSNAME),
            $metadataBbb = $this->cmf->getMetadataFor(MetadataFilterTestEntityBbb::CLASSNAME),
            $metadataCcc = $this->cmf->getMetadataFor(MetadataFilterTestEntityCcc::CLASSNAME),
        );

        $metadatas = $originalMetadatas;
        $metadatas = MetadataFilter::filter($metadatas, 'MetadataFilterTestEntityAaa');

        $this->assertContains($metadataAaa, $metadatas);
        $this->assertNotContains($metadataBbb, $metadatas);
        $this->assertNotContains($metadataCcc, $metadatas);
        $this->assertCount(1, $metadatas);

        $metadatas = $originalMetadatas;
        $metadatas = MetadataFilter::filter($metadatas, 'MetadataFilterTestEntityBbb');

        $this->assertNotContains($metadataAaa, $metadatas);
        $this->assertContains($metadataBbb, $metadatas);
        $this->assertNotContains($metadataCcc, $metadatas);
        $this->assertCount(1, $metadatas);

        $metadatas = $originalMetadatas;
        $metadatas = MetadataFilter::filter($metadatas, 'MetadataFilterTestEntityCcc');

        $this->assertNotContains($metadataAaa, $metadatas);
        $this->assertNotContains($metadataBbb, $metadatas);
        $this->assertContains($metadataCcc, $metadatas);
        $this->assertCount(1, $metadatas);
    }

    public function testFilterWithString2()
    {
        $originalMetadatas = array(
            $metadataFoo    = $this->cmf->getMetadataFor(MetadataFilterTestEntityFoo::CLASSNAME),
            $metadataFooBar = $this->cmf->getMetadataFor(MetadataFilterTestEntityFooBar::CLASSNAME),
            $metadataBar    = $this->cmf->getMetadataFor(MetadataFilterTestEntityBar::CLASSNAME),
        );

        $metadatas = $originalMetadatas;
        $metadatas = MetadataFilter::filter($metadatas, 'MetadataFilterTestEntityFoo');

        $this->assertContains($metadataFoo, $metadatas);
        $this->assertContains($metadataFooBar, $metadatas);
        $this->assertNotContains($metadataBar, $metadatas);
        $this->assertCount(2, $metadatas);
    }

    public function testFilterWithArray()
    {
        $originalMetadatas = array(
            $metadataAaa = $this->cmf->getMetadataFor(MetadataFilterTestEntityAaa::CLASSNAME),
            $metadataBbb = $this->cmf->getMetadataFor(MetadataFilterTestEntityBbb::CLASSNAME),
            $metadataCcc = $this->cmf->getMetadataFor(MetadataFilterTestEntityCcc::CLASSNAME),
        );

        $metadatas = $originalMetadatas;
        $metadatas = MetadataFilter::filter($metadatas, array(
            'MetadataFilterTestEntityAaa',
            'MetadataFilterTestEntityCcc',
        ));

        $this->assertContains($metadataAaa, $metadatas);
        $this->assertNotContains($metadataBbb, $metadatas);
        $this->assertContains($metadataCcc, $metadatas);
        $this->assertCount(2, $metadatas);
    }

    public function testFilterWithRegex()
    {
        $originalMetadatas = array(
            $metadataFoo    = $this->cmf->getMetadataFor(MetadataFilterTestEntityFoo::CLASSNAME),
            $metadataFooBar = $this->cmf->getMetadataFor(MetadataFilterTestEntityFooBar::CLASSNAME),
            $metadataBar    = $this->cmf->getMetadataFor(MetadataFilterTestEntityBar::CLASSNAME),
        );

        $metadatas = $originalMetadatas;
        $metadatas = MetadataFilter::filter($metadatas, 'Foo$');

        $this->assertContains($metadataFoo, $metadatas);
        $this->assertNotContains($metadataFooBar, $metadatas);
        $this->assertNotContains($metadataBar, $metadatas);
        $this->assertCount(1, $metadatas);

        $metadatas = $originalMetadatas;
        $metadatas = MetadataFilter::filter($metadatas, 'Bar$');

        $this->assertNotContains($metadataFoo, $metadatas);
        $this->assertContains($metadataFooBar, $metadatas);
        $this->assertContains($metadataBar, $metadatas);
        $this->assertCount(2, $metadatas);
    }
}

/** @Entity */
class MetadataFilterTestEntityAaa
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") */
    protected $id;
}

/** @Entity */
class MetadataFilterTestEntityBbb
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") */
    protected $id;
}

/** @Entity */
class MetadataFilterTestEntityCcc
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") */
    protected $id;
}

/** @Entity */
class MetadataFilterTestEntityFoo
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") */
    protected $id;
}

/** @Entity */
class MetadataFilterTestEntityBar
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") */
    protected $id;
}

/** @Entity */
class MetadataFilterTestEntityFooBar
{
    const CLASSNAME = __CLASS__;

    /** @Id @Column(type="integer") */
    protected $id;
}
