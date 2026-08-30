Strict Loading
==============

.. note::

    Strict loading is a proposal for Doctrine ORM 4 and is being discussed in
    `discussion #10931 <https://github.com/doctrine/orm/discussions/10931>`_.

Lazy loading is convenient, but it hides database access behind ordinary
property reads. The result is the N+1 query problem: a loop over 100 entities
that touches a lazy association issues 101 queries, and nothing in the code
shows that this happens.

Strict loading turns that implicit database access into a reported violation.
The idea comes from Ruby on Rails, which has had
`strict loading <https://guides.rubyonrails.org/active_record_querying.html#strict-loading>`_
since 6.1 (`ActiveRecord::Base#strict_loading!
<https://api.rubyonrails.org/classes/ActiveRecord/Core.html#method-i-strict_loading-21>`_).
The typical setup is to fetch everything a request needs up front - with fetch
joins, ``Query::setFetchMode()`` or an eager fetch mode - and then forbid
further loading for the rest of the request, for example while rendering a
template or serializing a response.

Enabling strict loading
-----------------------

Strict loading is configured per ``EntityManager`` and is disabled by default:

.. code-block:: php

    <?php
    use Doctrine\ORM\StrictLoading\StrictLoadingMode;

    $strictLoading = $entityManager->getConfiguration()->getStrictLoading();
    $strictLoading->setMode(StrictLoadingMode::All);

From now on, loading an uninitialized entity or collection throws
``Doctrine\ORM\Exception\StrictLoadingViolation``:

.. code-block:: php

    <?php
    $article = $entityManager->find(Article::class, 1);

    echo $article->getAuthor()->getName();
    // Strict loading violation: lazily loading entity App\Entity\User(42) is not
    // allowed here. Fetch the data explicitly (fetch join, Query::setFetchMode()
    // or an eager fetch mode), or wrap the offending code in
    // Doctrine\ORM\StrictLoading\StrictLoading::allow().

Fetching the association explicitly makes the code work again:

.. code-block:: php

    <?php
    $article = $entityManager->createQuery(
        'SELECT a, u FROM App\Entity\Article a JOIN a.author u WHERE a.id = :id',
    )->setParameter('id', 1)->getSingleResult();

    echo $article->getAuthor()->getName(); // no lazy load, no violation

Modes
-----

``StrictLoadingMode::Disabled``
    Lazy loading is allowed. This is the default and the historical behavior.

``StrictLoadingMode::NPlusOneOnly``
    Only lazy loads that repeat are reported. The first lazy load of a given
    association - or of a given entity class, for to-one references - is
    allowed, every following one is a violation. This is the mode to start with
    in an existing application, because it only complains about loads that
    actually degrade into an N+1 query.

``StrictLoadingMode::All``
    Every lazy load is reported. All data has to be fetched explicitly.

The two active modes are the ones Rails offers as ``:n_plus_one_only`` and
``:all``.

Limiting strict loading to part of a request
--------------------------------------------

The mode can be changed at any point, which is how you separate "fetching" from
"rendering":

.. code-block:: php

    <?php
    // Controller: fetch everything the view needs.
    $users = $repository->findAll(['articles']);

    // View: no database access allowed from here on.
    $strictLoading->setMode(StrictLoadingMode::All);

    return $this->render('users.html.twig', ['users' => $users]);

Reset the mode once per request - in a ``kernel.request`` listener, or wherever
the application sets up its unit of work - so that a request that switched to
``All`` does not affect the next one.

``allow()`` is the escape hatch for code that knowingly lazy loads. It takes a
callback because the previous state has to be restored even when the callback
throws:

.. code-block:: php

    <?php
    $author = $strictLoading->allow(static fn (): User => $article->getAuthor());

Reporting instead of throwing
-----------------------------

By default a violation is thrown. Passing a different
``StrictLoadingViolationHandler`` lets the load happen and only records it,
which is useful to introduce strict loading into an existing application, or to
run in ``NPlusOneOnly`` mode in production while the test suite throws. Rails
makes the same distinction with
`config.active_record.action_on_strict_loading_violation
<https://guides.rubyonrails.org/configuring.html#config-active-record-action-on-strict-loading-violation>`_,
which is either ``:raise`` or ``:log``:

.. code-block:: php

    <?php
    use Doctrine\ORM\StrictLoading\LogViolation;
    use Doctrine\ORM\StrictLoading\StrictLoading;
    use Doctrine\ORM\StrictLoading\StrictLoadingMode;

    $entityManager->getConfiguration()->setStrictLoading(new StrictLoading(
        StrictLoadingMode::NPlusOneOnly,
        new LogViolation($logger),
    ));

A custom handler receives a ``LazyLoad`` describing what was about to be
loaded, and may for instance add the violation to a profiler collector:

.. code-block:: php

    <?php
    use Doctrine\ORM\StrictLoading\LazyLoad;
    use Doctrine\ORM\StrictLoading\StrictLoadingMode;
    use Doctrine\ORM\StrictLoading\StrictLoadingViolationHandler;

    final class CollectViolations implements StrictLoadingViolationHandler
    {
        /** @var list<LazyLoad> */
        public array $violations = [];

        public function violation(LazyLoad $lazyLoad, StrictLoadingMode $mode): void
        {
            $this->violations[] = $lazyLoad;
        }
    }

What counts as a violation
--------------------------

Reported:

-  initializing an entity reference (a proxy), including references created by
   ``EntityManager::getReference()``;
-  loading an uninitialized collection;
-  a query that an uninitialized ``EXTRA_LAZY`` collection runs on its own -
   ``count()``, ``contains()``, ``containsKey()``, ``get()``, ``first()``,
   ``slice()`` and ``matching()``.

Never reported, because the ORM loads on purpose there:

-  everything that happens during ``flush()``, including change set
   computation, orphan removal and cascades;
-  ``persist()``, ``remove()``, ``refresh()`` and ``lock()``;
-  explicit initialization through ``EntityManager::initializeObject()``.

Not reported, because no lazy loading is involved: fetch joins, eager fetch
modes (``fetch: 'EAGER'`` and ``Query::setFetchMode()``, which load in batches),
``EntityManager::find()`` and DQL queries. Inverse to-one associations are
loaded while hydrating the owning entity, not lazily, and are therefore not
reported either - use an eager fetch mode or a fetch join to avoid the query
per row.

Caveats
-------

Entity references are shared through the identity map, so a violation names the
entity that was about to be loaded, not the property that was read. Collection
violations name the owning class and the field.

In ``NPlusOneOnly`` mode, "repeated" means "seen before in the current scope".
The scope is reset by ``EntityManager::clear()`` and by
``StrictLoading::reset()`` - call the latter once per request in a long-running
worker.
