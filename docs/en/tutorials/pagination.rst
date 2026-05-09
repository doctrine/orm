Pagination
==========

Doctrine ORM provides two pagination strategies for DQL queries. Both handle
the low-level SQL plumbing, but they make different trade-offs:

.. list-table::
   :header-rows: 1

   * - Feature
     - Offset ``Paginator``
     - ``CursorPaginator``
   * - Total count
     - Yes (extra query)
     - Yes (extra query)
   * - Random access to page N
     - Yes
     - No
   * - Stable under concurrent inserts/deletes
     - No
     - Yes
   * - Performance on deep pages
     - Degrades (OFFSET scan)
     - Constant (index range scan)
   * - Requires deterministic ORDER BY
     - No
     - Yes

Choose the **Offset Paginator** when you need random access to an arbitrary
page number.

Choose the **Cursor Paginator** when you need stable, high-performance
pagination on large datasets and a simple previous/next navigation is
sufficient. A total count is also available via ``getTotalCount()``, at the
cost of an extra ``COUNT`` query.

Offset-Based Pagination
-----------------------

Doctrine ORM ships with a Paginator for DQL queries. It
has a very simple API and implements the SPL interfaces ``Countable`` and
``IteratorAggregate``.

.. code-block:: php

    <?php
    use Doctrine\ORM\Tools\Pagination\Paginator;

    $dql = "SELECT p, c FROM BlogPost p JOIN p.comments c";
    $query = $entityManager->createQuery($dql)
                           ->setFirstResult(0)
                           ->setMaxResults(100);

    $paginator = new Paginator($query, fetchJoinCollection: true);

    $c = count($paginator);
    foreach ($paginator as $post) {
        echo $post->getHeadline() . "\n";
    }

Paginating Doctrine queries is not as simple as you might think in the
beginning. If you have complex fetch-join scenarios with one-to-many or
many-to-many associations using the "default" LIMIT functionality of database
vendors is not sufficient to get the correct results.

By default the pagination extension does the following steps to compute the
correct result:

- Perform a Count query using `DISTINCT` keyword.
- Perform a Limit Subquery with `DISTINCT` to find all ids of the entity in from on the current page.
- Perform a WHERE IN query to get all results for the current page.

This behavior is only necessary if you actually fetch join a to-many
collection. You can disable this behavior by setting the
``fetchJoinCollection`` argument to ``false``; in that case only 2 instead of the 3 queries
described are executed. We hope to automate the detection for this in
the future.

.. note::

    ``fetchJoinCollection`` argument set to ``true`` might affect results if you use aggregations in your query.

By using the ``Paginator::HINT_ENABLE_DISTINCT`` you can instruct doctrine that the query to be executed
will not produce "duplicate" rows (only to-one relations are joined), thus the SQL limit will work as expected.
In this way the `DISTINCT` keyword will be omitted and can bring important performance improvements.

.. code-block:: php

    <?php
    use Doctrine\ORM\Tools\Pagination\Paginator;

    $dql = "SELECT u, p FROM User u JOIN u.mainPicture p";
    $query = $entityManager->createQuery($dql)
                           ->setHint(Paginator::HINT_ENABLE_DISTINCT, false)
                           ->setFirstResult(0)
                           ->setMaxResults(100);

Cursor-Based Pagination
-----------------------

Doctrine ORM ships with a ``CursorPaginator`` for cursor-based pagination of DQL queries.
Unlike offset-based pagination, cursor pagination uses opaque pointers (cursors) derived
from the last seen row to fetch the next or previous page. This makes it stable and
performant on large datasets — no matter how deep you paginate, the database always uses
an index range scan instead of skipping rows.

.. note::

    Cursor pagination requires a **deterministic ORDER BY clause**. Every column
    combination used for sorting must uniquely identify a position in the result set.
    A common pattern is to sort by a timestamp and then by primary key as a tie-breaker.

Constructor
~~~~~~~~~~~

.. code-block:: php

    <?php
    new CursorPaginator(
        Query|QueryBuilder $query,
        bool $queryProducesDuplicates = true,
    )

``$queryProducesDuplicates``
    Set to ``true`` (default) when the query joins a to-many collection.
    The paginator then uses a two-query strategy (ID subquery + ``WHERE IN``)
    to return the correct number of root entities despite duplicate rows.
    Set to ``false`` when only to-one joins are present — this avoids the
    subquery overhead and is equivalent to passing ``fetchJoinCollection: false``
    to the offset-based ``Paginator``.
    However, passing ``false`` on a query that joins a to-many relation is not
    detected — arbitrary joins can produce duplicate root entities silently,
    leading to a corrupt result set.

Basic Usage
~~~~~~~~~~~

The ``$cursor`` parameter accepts either an encoded string produced by a previous call to
``getNextCursorAsString()`` or ``getPreviousCursorAsString()``, or a ``Cursor`` instance
returned by ``getNextCursor()`` or ``getPreviousCursor()``. On the first request it is
``null`` or an empty string ``''`` — both are treated identically as the first page.
It is typically read from the incoming HTTP query string:

.. code-block:: php

    $cursor = $_GET['cursor'] ?? null; // null or '' on the first page

.. code-block:: php

    <?php
    use Doctrine\ORM\Tools\Pagination\CursorPaginator;

    $dql = 'SELECT p FROM BlogPost p ORDER BY p.createdAt DESC, p.id DESC';
    $query = $entityManager->createQuery($dql);

    $paginator = (new CursorPaginator($query))
        ->paginate(cursor: $cursor, limit: 15);

    foreach ($paginator as $post) {
        echo $post->getTitle() . "\n";
    }

    echo $paginator->getPreviousCursorAsString(); // previous encoded cursor string
    echo $paginator->getNextCursorAsString();     // next encoded cursor string

Navigating Pages
~~~~~~~~~~~~~~~~

Pass the encoded cursor back on subsequent requests to move forward or backward:

.. code-block:: php

    <?php
    // Next page
    $paginator->paginate(cursor: $nextCursor, limit: 15);

    // Previous page
    $paginator->paginate(cursor: $previousCursor, limit: 15);

The cursor is an encoded string containing the location at which the next query should begin fetching results, along with the navigation direction.

API Reference
~~~~~~~~~~~~~

``CursorPaginator::paginate(Cursor|string|null $cursor, int $limit): self``
    Executes the query and stores the results. Accepts either an encoded cursor
    string or a ``Cursor`` instance directly. Fetches ``$limit + 1`` rows to
    detect whether a further page exists, then trims the extra row. Returns
    ``$this`` for chaining.

``CursorPaginator::getNextCursor(): Cursor``
    Returns the ``Cursor`` object for the next page. Throws a ``LogicException``
    if there is no next page — call ``hasNextPage()`` first.

``CursorPaginator::getPreviousCursor(): Cursor``
    Returns the ``Cursor`` object for the previous page. Throws a ``LogicException``
    if there is no previous page — call ``hasPreviousPage()`` first.

``CursorPaginator::getNextCursorAsString(): string``
    Returns the encoded cursor to retrieve the next page. Throws a
    ``LogicException`` if there is no next page — call ``hasNextPage()`` first.

``CursorPaginator::getPreviousCursorAsString(): string``
    Returns the encoded cursor to retrieve the previous page. Throws a
    ``LogicException`` if there is no previous page — call ``hasPreviousPage()`` first.

``CursorPaginator::hasNextPage(): bool``
    Returns whether a next page is available.

``CursorPaginator::hasPreviousPage(): bool``
    Returns whether a previous page is available.

``CursorPaginator::hasToPaginate(): bool``
    Returns whether either a next or previous page exists (i.e. the result
    set spans more than one page).

``CursorPaginator::getValues(): array``
    Returns the raw entity array for the current page.

``CursorPaginator::getItems(): array``
    Returns an array of ``CursorItem`` objects, each wrapping an entity and its
    individual ``Cursor``. Useful when you need per-row cursors.

``CursorPaginator::getCursorForItem(mixed $item, bool $isNext = true): Cursor``
    Builds a ``Cursor`` pointing at a specific entity. ``$isNext = true`` means
    "start *after* this item"; ``false`` means "start *before* this item".

``CursorPaginator::countPageItems(): int``
    Returns the number of items on the current page. Throws a ``LogicException``
    if ``paginate()`` has not been called yet.

``CursorPaginator::getTotalCount(): int``
    Executes an extra ``COUNT`` query and returns the total number of matching
    root entities, ignoring the cursor and limit. Use this when you need to
    display a total result count alongside previous/next navigation.

**Next page**

.. code-block:: sql

    SELECT ...
    FROM   post p
    WHERE  (p.created_at < :cursor_val_0)
       OR  (p.created_at = :cursor_val_0 AND p.id < :cursor_id_1)
    ORDER  BY p.created_at DESC, p.id DESC
    LIMIT  16   -- limit + 1

**Previous page**

.. code-block:: sql

    SELECT ...
    FROM   post p
    WHERE  (p.created_at > :cursor_val_0)
       OR  (p.created_at = :cursor_val_0 AND p.id > :cursor_id_1)
    ORDER  BY p.created_at ASC, p.id ASC   -- reversed
    LIMIT  16

HTML Template Example
~~~~~~~~~~~~~~~~~~~~~

The following example shows how to render a paginated list with previous/next
navigation links using the ``CursorPaginator`` in a PHP template:

.. literalinclude:: pagination/cursor-pagination.php
   :language: php

Cursor Encoding
~~~~~~~~~~~~~~~

A cursor is serialized to a URL-safe string via ``Cursor::encodeToString()`` and
deserialized back via the static ``Cursor::fromEncodedString()``. The format is a
JSON object encoded with URL-safe Base64 (no padding):

.. code-block:: json

    {
        "p.createdAt": "2024-01-15T10:30:00+00:00",
        "p.id": 42,
        "_isNext": true
    }

The ``_isNext`` flag distinguishes next-page cursors from previous-page cursors.
All other keys are the DQL path expressions (``alias.field``) of the ``ORDER BY``
columns, and their values are the database representations of the pivot row's
field values.

If you need a different serialization format (e.g. encryption), build it on top of
a ``Cursor`` instance: call ``$cursor->toArray()`` to get the raw data, apply your
own encoding, and reconstruct with ``new Cursor($parameters, $isNext)``.

Limitations
~~~~~~~~~~~

- Every ``ORDER BY`` column must map to an entity field. Raw SQL expressions or
  computed columns in ``ORDER BY`` are not supported.
- The query must have at least one ``ORDER BY`` item; the paginator throws a
  ``LogicException`` otherwise.
