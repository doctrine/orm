<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\ParserResult;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\QuerySetMapping;
use Doctrine\ORM\Tools\Pagination\LimitSubqueryOutputWalker;
use Doctrine\ORM\Tools\Pagination\WhereInWalker;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\DDC117\DDC117ApproveChanges;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;

use function assert;

/**
 * Tests the types the SqlWalker records for the bind parameters of a query.
 */
class QuerySetMappingWalkerTest extends OrmTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->getTestEntityManager();
    }

    /** @param array<string|int, string> $expected */
    #[DataProvider('provideMappedParameters')]
    public function testItRecordsTheTypeOfTheFieldBoundParametersAreUsedAgainst(string $dql, array $expected): void
    {
        self::assertSame($expected, $this->parse($dql)->typeMappings);
    }

    /** @return iterable<string, array{string, array<string|int, string>}> */
    public static function provideMappedParameters(): iterable
    {
        yield 'comparison' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.username = :name',
            ['name' => 'string'],
        ];

        yield 'comparison with the parameter on the left' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE :name = u.username',
            ['name' => 'string'],
        ];

        yield 'comparison with an operator other than equality' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.id > :id',
            ['id' => 'integer'],
        ];

        yield 'several comparisons' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.username = :name AND u.id = :id',
            ['name' => 'string', 'id' => 'integer'],
        ];

        yield 'in list' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.id IN (:a, :b)',
            ['a' => 'integer', 'b' => 'integer'],
        ];

        yield 'between' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.id BETWEEN :from AND :to',
            ['from' => 'integer', 'to' => 'integer'],
        ];

        yield 'update item' => [
            'UPDATE ' . CmsUser::class . ' u SET u.username = :name WHERE u.id = :id',
            ['name' => 'string', 'id' => 'integer'],
        ];

        yield 'single valued association, mapped to the identifier of the target' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.email = :email',
            ['email' => 'integer'],
        ];

        yield 'positional parameter' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.username = ?1',
            [1 => 'string'],
        ];

        yield 'parameter on a joined alias' => [
            'SELECT u FROM ' . CmsUser::class . ' u JOIN u.email e WHERE e.email = :mail',
            ['mail' => 'string'],
        ];

        // The parameter belongs to the comparison inside the subselect, not to the one the
        // subselect is an operand of: it maps to the string field, not to the integer one.
        yield 'parameter inside a subselect' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.id = '
                . '(SELECT MAX(u2.id) FROM ' . CmsUser::class . ' u2 WHERE u2.username = :name)',
            ['name' => 'string'],
        ];
    }

    #[DataProvider('provideUnmappedParameters')]
    public function testItRecordsNothingForParametersNotDirectlyComparedToAField(string $dql): void
    {
        self::assertTrue($this->parse($dql)->isEmpty());
    }

    /** @return iterable<string, array{string}> */
    public static function provideUnmappedParameters(): iterable
    {
        yield 'arithmetic operand' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.id = SIZE(u.phonenumbers) + :p',
        ];

        yield 'function operand' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.username = CONCAT(:a, :b)',
        ];

        yield 'function operand inside a subselect' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.id = '
                . '(SELECT MAX(u2.id) FROM ' . CmsUser::class . ' u2 WHERE u2.username = CONCAT(:p, \'x\'))',
        ];

        yield 'both sides are parameters' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE :a = :b',
        ];

        yield 'like pattern' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.username LIKE :pattern',
        ];

        yield 'collection member' => [
            'SELECT u FROM ' . CmsUser::class . ' u WHERE :phone MEMBER OF u.phonenumbers',
        ];
    }

    public function testAnAssociationWithACompositeIdentifierIsStillRejectedAsBefore(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('A single-valued association path expression to an entity with a composite primary key is not supported.');

        $this->parse('SELECT a FROM ' . DDC117ApproveChanges::class . ' a WHERE a.reference = :ref');
    }

    /**
     * WhereInWalker builds its AST by hand, wrapping the path expression in nodes the parser
     * would have collapsed itself.
     */
    public function testItRecordsTheFieldOfAPathExpressionBuiltByATreeWalker(): void
    {
        $query = new Query($this->entityManager);
        $query->setDQL('SELECT u FROM ' . CmsUser::class . ' u');
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [WhereInWalker::class]);
        $query->setHint(WhereInWalker::HINT_PAGINATOR_HAS_IDS, true);

        $qsm = (new Parser($query))->parse()->getQuerySetMapping();

        self::assertSame('integer', $qsm->getParameterType(WhereInWalker::PAGINATOR_ID_ALIAS));
    }

    /**
     * LimitSubqueryOutputWalker clones itself and walks the statement a second time into the
     * very same ParserResult, so parameters end up being recorded twice.
     */
    public function testAParameterRecordedTwiceByTheSameWalkIsNotAmbiguous(): void
    {
        $query = new Query($this->entityManager);
        $query->setDQL('SELECT u FROM ' . CmsUser::class . ' u WHERE u.username = :p ORDER BY u.name ASC');
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, LimitSubqueryOutputWalker::class);
        $query->setFirstResult(0)->setMaxResults(10);
        $query->getSQL();

        $parserResult = (new ReflectionProperty(Query::class, 'parserResult'))->getValue($query);
        assert($parserResult instanceof ParserResult);
        $qsm = $parserResult->getQuerySetMapping();

        self::assertSame('string', $qsm->getParameterType('p'));
        self::assertSame([], $qsm->ambiguousParameters);
    }

    public function testAParameterUsedAgainstTwoFieldsIsAmbiguous(): void
    {
        $qsm = $this->parse(
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.username = :p OR u.id = :p',
        );

        self::assertTrue($qsm->isEmpty());
        self::assertSame(['p' => true], $qsm->ambiguousParameters);
    }

    /**
     * A type name resolves to a single Type instance, converting values the one way, so two
     * fields sharing a type leave nothing to be ambiguous about.
     */
    public function testAParameterUsedAgainstTwoFieldsOfTheSameTypeIsNotAmbiguous(): void
    {
        $qsm = $this->parse(
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.username = :p OR u.name = :p',
        );

        self::assertSame('string', $qsm->getParameterType('p'));
        self::assertSame([], $qsm->ambiguousParameters);
    }

    public function testAParameterUsedTwiceAgainstTheSameFieldIsNotAmbiguous(): void
    {
        $qsm = $this->parse(
            'SELECT u FROM ' . CmsUser::class . ' u WHERE u.username = :p OR u.username = :p',
        );

        self::assertSame('string', $qsm->getParameterType('p'));
        self::assertSame([], $qsm->ambiguousParameters);
    }

    private function parse(string $dql): QuerySetMapping
    {
        $query = new Query($this->entityManager);
        $query->setDQL($dql);

        return (new Parser($query))->parse()->getQuerySetMapping();
    }
}
