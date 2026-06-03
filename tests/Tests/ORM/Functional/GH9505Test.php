<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH9505Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH9505ReadonlyDateEntity::class);
    }

    public function testRefreshDoesNotThrowOnReadonlyObjectProperty(): void
    {
        $entity = new GH9505ReadonlyDateEntity(new DateTimeImmutable('2022-01-01'));

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->_em->refresh($entity);

        $this->assertSame('2022-01-01', $entity->date->format('Y-m-d'));
    }
}

#[ORM\Entity]
class GH9505ReadonlyDateEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public readonly int $id;

    public function __construct(
        #[ORM\Column(type: 'datetime_immutable', nullable: false)]
        public readonly DateTimeImmutable $date,
    ) {
    }
}
