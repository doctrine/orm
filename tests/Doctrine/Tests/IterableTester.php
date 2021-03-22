<?php

declare(strict_types=1);

namespace Doctrine\Tests;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use PHPUnit\Framework\Assert;

use function is_array;
use function iterator_to_array;

final class IterableTester
{
    public static function assertResultsAreTheSame(Query $query): void
    {
        $result   = $query->getResult();
        $iterable = $query->toIterable();

        Assert::assertSame($result, self::iterableToArray($iterable));

        $result   = $query->getResult(AbstractQuery::HYDRATE_ARRAY);
        $iterable = $query->toIterable([], AbstractQuery::HYDRATE_ARRAY);

        Assert::assertSame($result, self::iterableToArray($iterable));
    }

    /**
     * Copy the iterable into an array. If the iterable is already an array, return it.
     *
     * @return mixed[]
     */
    public static function iterableToArray(iterable $iterable): array
    {
        return is_array($iterable) ? $iterable : iterator_to_array($iterable, true);
    }
}
