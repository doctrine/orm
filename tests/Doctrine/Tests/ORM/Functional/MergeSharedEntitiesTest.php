<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Annotation as ORM;
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
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(MSEFile::class),
                $this->em->getClassMetadata(MSEPicture::class),
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

        $picture = $this->em->merge($picture);

        self::assertEquals($picture->file, $picture->otherFile, 'Identical entities must remain identical');
    }

    public function testMergeSharedManagedEntities()
    {
        $file    = new MSEFile;
        $picture = new MSEPicture;

        $picture->file      = $file;
        $picture->otherFile = $file;

        $this->em->persist($file);
        $this->em->persist($picture);
        $this->em->flush();
        $this->em->clear();

        $picture = $this->em->merge($picture);

        self::assertEquals($picture->file, $picture->otherFile, 'Identical entities must remain identical');
    }

    public function testMergeSharedDetachedSerializedEntities()
    {
        $file    = new MSEFile;
        $picture = new MSEPicture;

        $picture->file      = $file;
        $picture->otherFile = $file;

        $serializedPicture = serialize($picture);

        $this->em->persist($file);
        $this->em->persist($picture);
        $this->em->flush();
        $this->em->clear();

        $picture = $this->em->merge(unserialize($serializedPicture));

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

        $this->em->persist($admin1);

        $admin2->setSession('zeh current session data');

        self::assertSame($admin1, $this->em->merge($admin2));
        self::assertSame('zeh current session data', $admin1->getSession());
    }
}

/** @ORM\Entity */
class MSEPicture
{
    /** @ORM\Column(type="integer") @ORM\Id @ORM\GeneratedValue */
    public $id;

    /** @ORM\ManyToOne(targetEntity="MSEFile", cascade={"merge"}) */
    public $file;

    /** @ORM\ManyToOne(targetEntity="MSEFile", cascade={"merge"}) */
    public $otherFile;
}

/** @ORM\Entity */
class MSEFile
{
    /** @ORM\Column(type="integer") @ORM\Id @ORM\GeneratedValue(strategy="AUTO") */
    public $id;
}

/** @ORM\MappedSuperclass */
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

/** @ORM\Entity */
class MSEAdmin extends MSEUser
{
    /** @ORM\Column(type="integer") @ORM\Id @ORM\GeneratedValue(strategy="NONE") */
    public $id;
}
