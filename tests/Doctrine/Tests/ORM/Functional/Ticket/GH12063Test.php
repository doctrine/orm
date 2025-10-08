<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH12063Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH12063Association::class, GH12063Entity::class);
    }

    public function testLoadedAssociationWithBackedEnum(): void
    {
        $association       = new GH12063Association();
        $association->code = GH12063Code::One;

        $this->_em->persist($association);
        $this->_em->flush();
        $this->_em->clear();

        $entity              = new GH12063Entity();
        $entity->association = $this->_em->find(GH12063Association::class, GH12063Code::One);

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->assertNotNull($entity->id);
    }

    public function testProxyAssociationWithBackedEnum(): void
    {
        $association       = new GH12063Association();
        $association->code = GH12063Code::Two;

        $this->_em->persist($association);
        $this->_em->flush();
        $this->_em->clear();

        $entity              = new GH12063Entity();
        $entity->association = $this->_em->getReference(GH12063Association::class, GH12063Code::Two);

        $this->_em->persist($entity);
        $this->_em->flush();

        $this->assertNotNull($entity->id);
    }
}

enum GH12063Code: string
{
    case One = 'one';
    case Two = 'two';
}

#[Entity]
class GH12063Association
{
    #[Id]
    #[Column]
    public GH12063Code $code;
}

#[Entity]
class GH12063Entity
{
    #[Id]
    #[Column]
    #[GeneratedValue]
    public int|null $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(referencedColumnName: 'code')]
    public GH12063Association $association;
}
