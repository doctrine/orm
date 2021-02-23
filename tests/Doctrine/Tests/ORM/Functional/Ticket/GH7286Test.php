<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7286Test extends OrmFunctionalTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH7286Entity::class,
            ]
        );

        $this->em->persist(new GH7286Entity('foo', 1));
        $this->em->persist(new GH7286Entity('foo', 2));
        $this->em->persist(new GH7286Entity('bar', 3));
        $this->em->persist(new GH7286Entity(null, 4));
        $this->em->flush();
        $this->em->clear();
    }

    public function testAggregateExpressionInFunction() : void
    {
        $query = $this->em->createQuery(
            'SELECT CONCAT(e.type, MIN(e.version)) pair'
            . ' FROM ' . GH7286Entity::class . ' e'
            . ' WHERE e.type IS NOT NULL'
            . ' GROUP BY e.type'
            . ' ORDER BY e.type'
        );

        self::assertSame(
            [
                ['pair' => 'bar3'],
                ['pair' => 'foo1'],
            ],
            $query->getArrayResult()
        );
    }

    /**
     * @group DDC-1091
     */
    public function testAggregateFunctionInCustomFunction() : void
    {
        $this->em->getConfiguration()->addCustomStringFunction('CC', GH7286CustomConcat::class);

        $query = $this->em->createQuery(
            'SELECT CC(e.type, MIN(e.version)) pair'
            . ' FROM ' . GH7286Entity::class . ' e'
            . ' WHERE e.type IS NOT NULL AND e.type != :type'
            . ' GROUP BY e.type'
        );
        $query->setParameter('type', 'bar');

        self::assertSame(
            ['pair' => 'foo1'],
            $query->getSingleResult()
        );
    }
}

/**
 * @ORM\Entity
 */
class GH7286Entity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\Column(nullable=true)
     *
     * @var string|null
     */
    public $type;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $version;

    public function __construct(?string $type, int $version)
    {
        $this->type    = $type;
        $this->version = $version;
    }
}

class GH7286CustomConcat extends FunctionNode
{
    /** @var Node */
    private $first;

    /** @var Node */
    private $second;

    public function parse(Parser $parser) : void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->first = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->second = $parser->StringPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $walker) : string
    {
        return $walker->getConnection()->getDatabasePlatform()->getConcatExpression(
            $this->first->dispatch($walker),
            $this->second->dispatch($walker)
        );
    }
}
