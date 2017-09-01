<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\ValueGenerators\BarGenerator;
use Doctrine\Tests\Models\ValueGenerators\CompositeGeneratedIdentifier;
use Doctrine\Tests\Models\ValueGenerators\FooGenerator;
use Doctrine\Tests\OrmFunctionalTestCase;

class ValueGeneratorsTest extends OrmFunctionalTestCase
{

    public function setUp()
    {
        $this->useModelSet('valueGenerators');
        parent::setUp();
    }

    public function testCompositeIdentifierWithMultipleGenerators() : void
    {
        $entity = new CompositeGeneratedIdentifier();
        $this->em->persist($entity);
        $this->em->flush();

        self::assertSame(FooGenerator::VALUE, $entity->getA());
        self::assertSame(BarGenerator::VALUE, $entity->getB());

        $this->em->clear();

        $entity = $this->getEntityManager()->find(
            CompositeGeneratedIdentifier::class,
            ['a' => FooGenerator::VALUE, 'b' => BarGenerator::VALUE]
        );
        self::assertNotNull($entity);
    }
}
