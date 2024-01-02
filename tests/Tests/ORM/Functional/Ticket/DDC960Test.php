<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Version;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class DDC960Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC960Root::class, DDC960Child::class);
    }

    #[Group('DDC-960')]
    public function testUpdateRootVersion(): void
    {
        $child = new DDC960Child('Test');
        $this->_em->persist($child);
        $this->_em->flush();

        $child->setName('Test2');

        $this->_em->flush();

        self::assertEquals(2, $child->getVersion());
    }
}

#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorMap(['root' => 'DDC960Root', 'child' => 'DDC960Child'])]
class DDC960Root
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private int $id;

    #[Column(type: 'integer')]
    #[Version]
    private int $version;

    public function getId(): int
    {
        return $this->id;
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}

#[Entity]
class DDC960Child extends DDC960Root
{
    public function __construct(
        #[Column(type: 'string', length: 255)]
        private string $name,
    ) {
    }

    public function setName($name): void
    {
        $this->name = $name;
    }
}
