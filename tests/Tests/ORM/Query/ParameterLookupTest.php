<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ParameterLookupTest extends OrmTestCase
{
    /** @return iterable<string, array{string}> */
    public static function provideQueryKinds(): iterable
    {
        yield 'DQL' => ['dql'];
        yield 'native SQL' => ['native'];
        yield 'query builder' => ['builder'];
    }

    private function createQuery(string $kind): AbstractQuery|QueryBuilder
    {
        $em = $this->getTestEntityManager();

        return match ($kind) {
            'dql' => $em->createQuery(''),
            'native' => $em->createNativeQuery('', new ResultSetMapping()),
            'builder' => $em->createQueryBuilder(),
        };
    }

    #[DataProvider('provideQueryKinds')]
    public function testNormalizedNamesAndMissingParameters(string $kind): void
    {
        $query = $this->createQuery($kind);
        self::assertNull($query->getParameter('missing'));

        $numeric = new Parameter(0, 12);
        $named   = new Parameter('name', 'value');
        $padded  = new Parameter('00', 'distinct');
        $query->setParameters(new ArrayCollection([7 => $named, 99 => $numeric, 120 => $padded]));

        self::assertSame($named, $query->getParameter(':name'));
        self::assertSame($numeric, $query->getParameter(0));
        self::assertSame($numeric, $query->getParameter(':0'));
        self::assertSame($padded, $query->getParameter('00'));
        self::assertNull($query->getParameter('missing'));
    }

    #[DataProvider('provideQueryKinds')]
    public function testFirstDuplicateAndExternalMutation(string $kind): void
    {
        $query      = $this->createQuery($kind);
        $first      = new Parameter('duplicate', 1);
        $second     = new Parameter('duplicate', 2);
        $parameters = new ArrayCollection([7 => $first, 99 => $second]);
        $query->setParameters($parameters);

        self::assertSame($first, $query->getParameter('duplicate'));
        $parameters->remove(7);
        self::assertSame($second, $query->getParameter('duplicate'));
        $query->getParameters()->clear();
        self::assertNull($query->getParameter('duplicate'));
        $parameters->add($first);
        self::assertSame($first, $query->getParameter('duplicate'));
    }

    #[DataProvider('provideQueryKinds')]
    public function testSetParameterUpdatesTheFirstMatchingObject(string $kind): void
    {
        $query  = $this->createQuery($kind);
        $first  = new Parameter('name', 1);
        $second = new Parameter('name', 2);
        $query->setParameters(new ArrayCollection([$first, $second]));
        $query->setParameter(':name', 'changed', Types::STRING);

        self::assertSame($first, $query->getParameter('name'));
        self::assertSame('changed', $first->getValue());
        self::assertSame(Types::STRING, $first->getType());
        self::assertSame(2, $second->getValue());
        self::assertCount(2, $query->getParameters());
    }
}
