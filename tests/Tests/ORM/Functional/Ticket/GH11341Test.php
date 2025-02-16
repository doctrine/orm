<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;

class GH11341Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            IntegerBaseClass::class,
            IntegerFooEntity::class,
            IntegerBarEntity::class,
            StringAsIntBaseClass::class,
            StringAsIntFooEntity::class,
            StringAsIntBarEntity::class,
            StringBaseClass::class,
            StringFooEntity::class,
            StringBarEntity::class,
        ]);
    }

    public static function dqlStatements(): Generator
    {
        yield ['SELECT e FROM ' . IntegerBaseClass::class . ' e', '/WHERE [a-z]0_.type IN \(1, 2\)$/'];
        yield ['SELECT e FROM ' . IntegerFooEntity::class . ' e', '/WHERE [a-z]0_.type IN \(1\)$/'];
        yield ['SELECT e FROM ' . IntegerBarEntity::class . ' e', '/WHERE [a-z]0_.type IN \(2\)$/'];
        yield ['SELECT e FROM ' . StringAsIntBaseClass::class . ' e', '/WHERE [a-z]0_.type IN \(\'1\', \'2\'\)$/'];
        yield ['SELECT e FROM ' . StringAsIntFooEntity::class . ' e', '/WHERE [a-z]0_.type IN \(\'1\'\)$/'];
        yield ['SELECT e FROM ' . StringAsIntBarEntity::class . ' e', '/WHERE [a-z]0_.type IN \(\'2\'\)$/'];
        yield ['SELECT e FROM ' . StringBaseClass::class . ' e', '/WHERE [a-z]0_.type IN \(\'1\', \'2\'\)$/'];
        yield ['SELECT e FROM ' . StringFooEntity::class . ' e', '/WHERE [a-z]0_.type IN \(\'1\'\)$/'];
        yield ['SELECT e FROM ' . StringBarEntity::class . ' e', '/WHERE [a-z]0_.type IN \(\'2\'\)$/'];
    }

    #[DataProvider('dqlStatements')]
    public function testDiscriminatorValue(string $dql, string $expectedDiscriminatorValues): void
    {
        $query = $this->_em->createQuery($dql);
        $sql   = $query->getSQL();

        self::assertMatchesRegularExpression($expectedDiscriminatorValues, $sql);
    }

    public static function dqlStatementsForInstanceOf(): Generator
    {
        yield [IntegerBaseClass::class, IntegerFooEntity::class];
        yield [StringBaseClass::class, StringFooEntity::class];
        yield [StringAsIntBaseClass::class, StringAsIntFooEntity::class];
    }

    /**
     * @phpstan-param class-string $baseClass
     * @phpstan-param class-string $inheritedClass
     */
    #[DataProvider('dqlStatementsForInstanceOf')]
    public function testInstanceOf(string $baseClass, string $inheritedClass): void
    {
        $this->_em->persist(new $inheritedClass());
        $this->_em->flush();

        $dql = 'SELECT p FROM ' . $baseClass . ' p WHERE p INSTANCE OF ' . $baseClass;

        $query  = $this->_em->createQuery($dql);
        $result = $query->getResult();

        self::assertCount(1, $result);
        self::assertContainsOnlyInstancesOf($baseClass, $result);
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'integer_discriminator')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'integer')]
#[ORM\DiscriminatorMap([
    1 => IntegerFooEntity::class,
    2 => IntegerBarEntity::class,
])]
class IntegerBaseClass
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;
}

#[ORM\Entity]
class IntegerFooEntity extends IntegerBaseClass
{
}

#[ORM\Entity]
class IntegerBarEntity extends IntegerBaseClass
{
}

#[ORM\Entity]
#[ORM\Table(name: 'string_as_int_discriminator')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    1 => StringAsIntFooEntity::class,
    2 => StringAsIntBarEntity::class,
])]
class StringAsIntBaseClass
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;
}

#[ORM\Entity]
class StringAsIntFooEntity extends StringAsIntBaseClass
{
}

#[ORM\Entity]
class StringAsIntBarEntity extends StringAsIntBaseClass
{
}


#[ORM\Entity]
#[ORM\Table(name: 'string_discriminator')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    '1' => StringFooEntity::class,
    '2' => StringBarEntity::class,
])]
class StringBaseClass
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private int|null $id = null;
}

#[ORM\Entity]
class StringFooEntity extends StringBaseClass
{
}

#[ORM\Entity]
class StringBarEntity extends StringBaseClass
{
}
