Preloading Associations
=======================

.. note::

    ``EntityManager::preload()`` is a proposal for Doctrine ORM 4, discussed
    together with :doc:`strict loading <strict-loading>` in
    `discussion #10931 <https://github.com/doctrine/orm/discussions/10931>`_.

A fetch join or an eager fetch mode loads associations while a query is being
hydrated. That does not help for entities that are already in memory - from a
repository method, a paginator, the second level cache, or a previous preload.
Iterating over those and touching a lazy association is the N+1 problem, and
rewriting the original query is not always an option.

``preload()`` loads an association for many entities at once:

.. code-block:: php

    <?php
    $users = $entityManager->getRepository(User::class)->findAll();

    $entityManager->preload($users, ['articles']);

    foreach ($users as $user) {
        foreach ($user->getArticles() as $article) {
            // no queries here
        }
    }

One query is issued for all the collections together, in batches of
``Configuration::setEagerFetchBatchSize()`` entities (100 by default). This is
what Rails calls
`preload <https://guides.rubyonrails.org/active_record_querying.html#preload>`_,
as opposed to
`eager_load <https://guides.rubyonrails.org/active_record_querying.html#eager-load>`_,
which is a fetch join.

Fetching and preloading in one call
-----------------------------------

The repository finders take the paths directly, so the common case does not
need a second statement:

.. code-block:: php

    <?php
    $users = $repository->findAll(['articles']);
    $users = $repository->findBy(['active' => true], preload: ['articles.comments']);
    $user  = $repository->find($id, preload: ['articles']);
    $user  = $repository->findOneBy(['email' => $email], preload: ['articles']);

The same on a query, for the associations that a fetch join cannot cover - a
second collection next to one that is already joined, for instance:

.. code-block:: php

    <?php
    $users = $entityManager->createQuery(
        'SELECT u, a FROM App\Entity\User u LEFT JOIN u.articles a',
    )->preload(['address', 'articles.comments'])->getResult();

and on the query builder, which hands the paths to the query it creates:

.. code-block:: php

    <?php
    $users = $repository->createQueryBuilder('u')
        ->where('u.active = true')
        ->preload(['articles'])
        ->getQuery()
        ->getResult();

The paths are preloaded after hydration, so a query preload costs the same
queries as calling ``preload()`` on the result would. ``toIterable()`` throws
when paths are set: it hydrates one row at a time, so there is nothing to load
the associations for in one query.

``EntityManager::preload()`` and ``EntityRepository::preload()`` stay the way to
preload entities you already hold - from a paginator, an event listener, or a
previous preload - where there is no finder call to pass paths to.

Paths
-----

A path may walk several associations. Every step is batched over everything the
previous step loaded, so the number of queries depends on the depth of the path,
not on the number of entities:

.. code-block:: php

    <?php
    $entityManager->preload($users, ['articles.comments.author']);

Several paths can be preloaded at once:

.. code-block:: php

    <?php
    $entityManager->preload($users, ['articles.comments', 'address']);

Passing no path at all initializes the given entities themselves, which turns a
list of references into a single query:

.. code-block:: php

    <?php
    $references = array_map(
        static fn (int $id): User => $entityManager->getReference(User::class, $id),
        $ids,
    );

    $entityManager->preload($references);

What is loaded
--------------

============================  ==========================================
Association                   Queries per batch
============================  ==========================================
``ManyToOne``, owning
``OneToOne``                  1
``OneToMany``                 1
``ManyToMany``                2 (the join table, then the targets)
Inverse ``OneToOne``          0 - the ORM already loads it while
                              hydrating the owner
============================  ==========================================

A preload always loads the **whole** association: it marks collections as
initialized, so a partially filled collection would be indistinguishable from a
complete one. Use
:ref:`matching() <filtering-collections>` when a filtered subset is what you need
- it returns a separate result and leaves the collection alone.

Skipped silently:

-  associations that are already loaded, including collections filled by a fetch
   join, and collections that were preloaded before;
-  collections with unflushed changes - loading them would take a snapshot that
   contradicts those changes, so they initialize on their own later;
-  entities that are not managed by this ``EntityManager``.

Preloading an association that does not exist throws
``ORMInvalidArgumentException``, naming the class and the path: a typo must not
silently bring the N+1 back.

Ordering and indexing behave exactly as they do for a lazy load: the
association's ``orderBy`` is applied in SQL, and ``indexBy`` keys the collection.

Strict loading
--------------

``preload()`` is the way out of a
:doc:`strict loading <strict-loading>` violation for entities you already hold.
Fetch first, then forbid loading:

.. code-block:: php

    <?php
    $users = $repository->findAll(['articles.comments']);

    $strictLoading->setMode(StrictLoadingMode::All);

    return $this->render('users.html.twig', ['users' => $users]);

Preloading itself never triggers a violation.

Batching without ``preload()``
------------------------------

The same batching runs while a query is hydrated, for associations mapped with
``fetch: 'EAGER'`` or overridden per query:

.. code-block:: php

    <?php
    $query = $entityManager->createQuery('SELECT u FROM App\Entity\User u')
        ->setFetchMode(User::class, 'articles', ClassMetadata::FETCH_EAGER);

Batching is skipped, and the association is loaded one owner at a time, when:

-  the query is iterated with ``toIterable()`` - rows are hydrated one by one, so
   there is nothing to batch them with;
-  the result comes from the second level cache;
-  the association has a composite key on the side that has to be filtered;
-  ``indexBy`` names a column rather than a mapped field, because only the SQL
   result set can resolve that.
