<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7259Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH7259Space::class, GH7259File::class, GH7259FileVersion::class, GH7259Feed::class]);
    }

    /**
     * @group 7259
     */
    public function testPersistFileBeforeVersion() : void
    {
        $space = new GH7259Space();

        $this->em->persist($space);
        $this->em->flush();

        $feed        = new GH7259Feed();
        $feed->space = $space;

        $file              = new GH7259File();
        $file->space       = $space;
        $fileVersion       = new GH7259FileVersion();
        $fileVersion->file = $file;

        $this->em->persist($file);
        $this->em->persist($fileVersion);
        $this->em->persist($feed);

        $this->em->flush();

        self::assertNotNull($fileVersion->id);
    }

    /**
     * @group 7259
     */
    public function testPersistFileAfterVersion() : void
    {
        $space = new GH7259Space();

        $this->em->persist($space);
        $this->em->flush();
        $this->em->clear();

        $space = $this->em->find(GH7259Space::class, $space->id);

        $feed        = new GH7259Feed();
        $feed->space = $space;

        $file              = new GH7259File();
        $file->space       = $space;
        $fileVersion       = new GH7259FileVersion();
        $fileVersion->file = $file;

        $this->em->persist($fileVersion);
        $this->em->persist($file);
        $this->em->persist($feed);

        $this->em->flush();

        self::assertNotNull($fileVersion->id);
    }
}

/**
 * @ORM\Entity()
 */
class GH7259File
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH7259Space::class)
     * @ORM\JoinColumn(nullable=false)
     *
     * @var GH7259Space|null
     */
    public $space;
}

/**
 * @ORM\Entity()
 */
class GH7259FileVersion
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH7259File::class)
     * @ORM\JoinColumn(nullable=false)
     *
     * @var GH7259File|null
     */
    public $file;
}

/**
 * @ORM\Entity()
 */
class GH7259Space
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH7259File::class)
     * @ORM\JoinColumn(nullable=true)
     *
     * @var GH7259File|null
     */
    public $ruleFile;
}

/**
 * @ORM\Entity()
 */
class GH7259Feed
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH7259Space::class)
     * @ORM\JoinColumn(nullable=false)
     *
     * @var GH7259Space|null
     */
    public $space;
}
