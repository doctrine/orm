<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Tools\ToolsException;
use Doctrine\Tests\OrmFunctionalTestCase;

class MergeSharedEntitiesTest extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                $this->_em->getClassMetadata(MSEFile::class),
                $this->_em->getClassMetadata(MSEPicture::class),
                ]
            );
        } catch (ToolsException $ignored) {
        }
    }

    public function testMergeSharedNewEntities()
    {
        $file    = new MSEFile;
        $picture = new MSEPicture;

        $picture->file      = $file;
        $picture->otherFile = $file;

        $picture = $this->_em->merge($picture);

        self::assertEquals($picture->file, $picture->otherFile, 'Identical entities must remain identical');
    }

    public function testMergeSharedManagedEntities()
    {
        $file    = new MSEFile;
        $picture = new MSEPicture;

        $picture->file      = $file;
        $picture->otherFile = $file;

        $this->_em->persist($file);
        $this->_em->persist($picture);
        $this->_em->flush();
        $this->_em->clear();

        $picture = $this->_em->merge($picture);

        self::assertEquals($picture->file, $picture->otherFile, 'Identical entities must remain identical');
    }

    public function testMergeSharedDetachedSerializedEntities()
    {
        $file    = new MSEFile;
        $picture = new MSEPicture;

        $picture->file      = $file;
        $picture->otherFile = $file;

        $serializedPicture = serialize($picture);

        $this->_em->persist($file);
        $this->_em->persist($picture);
        $this->_em->flush();
        $this->_em->clear();

        $picture = $this->_em->merge(unserialize($serializedPicture));

        self::assertEquals($picture->file, $picture->otherFile, 'Identical entities must remain identical');
    }

    /**
     * @group DDC-2704
     */
    public function testMergeInheritedTransientPrivateProperties()
    {
        $admin1 = new MSEAdmin();
        $admin2 = new MSEAdmin();

        $admin1->id = 123;
        $admin2->id = 123;

        $this->_em->persist($admin1);

        $admin2->setSession('zeh current session data');

        self::assertSame($admin1, $this->_em->merge($admin2));
        self::assertSame('zeh current session data', $admin1->getSession());
    }
}

/** @Entity */
class MSEPicture
{
    /** @Column(type="integer") @Id @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="MSEFile", cascade={"merge"}) */
    public $file;

    /** @ManyToOne(targetEntity="MSEFile", cascade={"merge"}) */
    public $otherFile;
}

/** @Entity */
class MSEFile
{
    /** @Column(type="integer") @Id @GeneratedValue(strategy="AUTO") */
    public $id;
}

/** @MappedSuperclass */
abstract class MSEUser
{
    private $session; // intentionally transient property

    public function getSession()
    {
        return $this->session;
    }

    public function setSession($session)
    {
        $this->session = $session;
    }
}

/** @Entity */
class MSEAdmin extends MSEUser
{
    /** @Column(type="integer") @Id @GeneratedValue(strategy="NONE") */
    public $id;
}
