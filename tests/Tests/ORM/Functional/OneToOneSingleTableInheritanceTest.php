<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\OneToOneSingleTableInheritance\Cat;
use Doctrine\Tests\Models\OneToOneSingleTableInheritance\LitterBox;
use Doctrine\Tests\Models\OneToOneSingleTableInheritance\Pet;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function assert;

class OneToOneSingleTableInheritanceTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            Pet::class,
            Cat::class,
            LitterBox::class,
        );
    }

    /**
     * Tests a unidirectional one-to-one association mapping from an inheritance child class
     */
    #[Group('DDC-3517')]
    #[Group('#1265')]
    public function testFindFromOneToOneOwningSideJoinedTableInheritance(): void
    {
        $cat            = new Cat();
        $cat->litterBox = new LitterBox();

        $this->_em->persist($cat);
        $this->_em->persist($cat->litterBox);
        $this->_em->flush();
        $this->_em->clear();

        $foundCat = $this->_em->find(Pet::class, $cat->id);
        assert($foundCat instanceof Cat);

        self::assertInstanceOf(Cat::class, $foundCat);
        self::assertSame($cat->id, $foundCat->id);
        self::assertInstanceOf(LitterBox::class, $foundCat->litterBox);
        self::assertSame($cat->litterBox->id, $foundCat->litterBox->id);
    }
}
