<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\Pagination;

use Doctrine\ORM\Internal\SQLResultCasing;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

use function array_map;
use function array_sum;
use function array_values;
use function get_debug_type;
use function sprintf;

/**
 * Offset-based paginator, designed to be symmetric with {@see CursorPaginator}.
 *
 * The query and the pagination position are both passed to {@see paginate()},
 * which returns an immutable, iterable {@see WindowPage} rather than the
 * paginator itself. The paginator therefore holds no state beyond its
 * configuration: a single instance can be shared as a service and reused for
 * any query and any page. This avoids the implicit offset handling and the
 * stateful API of the legacy {@see Paginator}, which this class is intended to
 * replace.
 *
 * @template-covariant T
 * @implements PaginatorInterface<T, Window>
 */
final class OffsetPaginator implements PaginatorInterface
{
    use SQLResultCasing;
    use PaginatorQuery;

    /**
     * @param bool      $fetchJoinCollection Whether the query joins a collection (true by default).
     * @param bool|null $useOutputWalkers    Whether to force the use of an output walker. Null (default)
     *                                       lets the paginator decide.
     */
    public function __construct(
        private readonly bool $fetchJoinCollection = true,
        bool|null $useOutputWalkers = null,
    ) {
        $this->useOutputWalkers = $useOutputWalkers;
    }

    /**
     * Returns whether the query joins a collection.
     */
    public function getFetchJoinCollection(): bool
    {
        return $this->fetchJoinCollection;
    }

    /**
     * Executes the query for the given window and returns an immutable page.
     *
     * @param Window $position The window to fetch. Use {@see Window::fromPageNumberAndSize()}
     *                         to build it from a 1-based page number and a page size.
     *
     * @return WindowPage<T>
     *
     * @throws InvalidArgumentException If $position is not a {@see Window}.
     */
    public function paginate(Query|QueryBuilder $query, mixed $position = null): WindowPage
    {
        if (! $position instanceof Window) {
            throw new InvalidArgumentException(sprintf(
                'Expected an instance of %s, got %s.',
                Window::class,
                get_debug_type($position),
            ));
        }

        $query = $this->resolveQuery($query);

        /** @var list<T> $items */
        $items = array_values($this->getResultForOffset(
            $query,
            $position->getFirstResult(),
            $position->getMaxResults(),
            $this->fetchJoinCollection,
        ));

        try {
            $totalCount = (int) array_sum(array_map('current', $this->getCountQuery($query)->getScalarResult()));
        } catch (NoResultException) {
            $totalCount = 0;
        }

        return new WindowPage($items, $totalCount, $position);
    }
}
