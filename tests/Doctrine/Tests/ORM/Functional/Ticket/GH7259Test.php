<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

final class GH7259Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH7259Space::class, GH7259File::class, GH7259FileVersion::class, GH7259Feed::class]);
    }

    #[Group('GH-7259')]
    public function testPersistFileBeforeVersion(): void
    {
        $space = new GH7259Space();

        $this->_em->persist($space);
        $this->_em->flush();

        $feed        = new GH7259Feed();
        $feed->space = $space;

        $file              = new GH7259File();
        $file->space       = $space;
        $fileVersion       = new GH7259FileVersion();
        $fileVersion->file = $file;

        $this->_em->persist($file);
        $this->_em->persist($fileVersion);
        $this->_em->persist($feed);

        $this->_em->flush();

        self::assertNotNull($fileVersion->id);
    }

    #[Group('GH-7259')]
    public function testPersistFileAfterVersion(): void
    {
        $space = new GH7259Space();

        $this->_em->persist($space);
        $this->_em->flush();
        $this->_em->clear();

        $space = $this->_em->find(GH7259Space::class, $space->id);

        $feed        = new GH7259Feed();
        $feed->space = $space;

        $file              = new GH7259File();
        $file->space       = $space;
        $fileVersion       = new GH7259FileVersion();
        $fileVersion->file = $file;

        $this->_em->persist($fileVersion);
        $this->_em->persist($file);
        $this->_em->persist($feed);

        $this->_em->flush();

        self::assertNotNull($fileVersion->id);
    }
}

#[Entity]
class GH7259File
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7259Space|null */
    #[ManyToOne(targetEntity: GH7259Space::class)]
    #[JoinColumn(nullable: false)]
    public $space;
}

#[Entity]
class GH7259FileVersion
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7259File|null */
    #[ManyToOne(targetEntity: GH7259File::class)]
    #[JoinColumn(nullable: false)]
    public $file;
}

#[Entity]
class GH7259Space
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7259File|null */
    #[ManyToOne(targetEntity: GH7259File::class)]
    #[JoinColumn(nullable: true)]
    public $ruleFile;
}

#[Entity]
class GH7259Feed
{
    /** @var int */
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH7259Space|null */
    #[ManyToOne(targetEntity: GH7259Space::class)]
    #[JoinColumn(nullable: false)]
    public $space;
}
