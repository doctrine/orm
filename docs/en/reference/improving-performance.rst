Improving Performance
=====================

Bytecode Cache
--------------

It is highly recommended to make use of a bytecode cache like OPcache.
A bytecode cache removes the need for parsing PHP code on every
request and can greatly improve performance.

    "If you care about performance and don't use a bytecode
    cache then you don't really care about performance. Please get one
    and start using it."

    *Stas Malyshev, Core Contributor to PHP and Zend Employee*


Metadata and Query caches
-------------------------

As already mentioned earlier in the chapter about configuring
Doctrine, it is strongly discouraged to use Doctrine without a
Metadata and Query cache.

Operating Doctrine without these caches means
Doctrine will need to load your mapping information on every single
request and has to parse each DQL query on every single request.
This is a waste of resources.

The preferred cache adapter for metadata and query caches is a PHP file
cache like Symfony's
`PHP files adapter <https://symfony.com/doc/current/components/cache/adapters/php_files_adapter.html>`_.
This kind of cache serializes cache items and writes them to a file.
This allows for opcode caching to be used and provides high performance in most scenarios.

See :ref:`types-of-caches`

Alternative Query Result Formats
--------------------------------

Make effective use of the available alternative query result
formats like nested array graphs or pure scalar results, especially
in scenarios where data is loaded for read-only purposes.

Read-Only Entities
------------------

You can mark entities as read only. For details, see :ref:`attrref_entity`

This means that the entity marked as read only is never considered for updates.
During flush on the EntityManager these entities are skipped even if properties
changed.

Read-Only allows to persist new entities of a kind and remove existing ones,
they are just not considered for updates.

You can also explicitly mark individual entities read only directly on the
UnitOfWork via a call to ``markReadOnly()``:

.. code-block:: php

   $user = $entityManager->find(User::class, $id);
   $entityManager->getUnitOfWork()->markReadOnly($user);

Or you can set all objects that are the result of a query hydration to be
marked as read only with the following query hint:

.. code-block:: php

   $query = $entityManager->createQuery('SELECT u FROM App\\Entity\\User u');
   $query->setHint(Query::HINT_READ_ONLY, true);

   $users = $query->getResult();

Extra-Lazy Collections
----------------------

If entities hold references to large collections you will get performance and memory problems initializing them.
To solve this issue you can use the EXTRA_LAZY fetch-mode feature for collections. See the :doc:`tutorial <../tutorials/extra-lazy-associations>`
for more information on how this fetch mode works.

Temporarily change fetch mode in DQL
------------------------------------

See :ref:`dql-temporarily-change-fetch-mode`


Apply Best Practices
--------------------

A lot of the points mentioned in the Best Practices chapter will
also positively affect the performance of Doctrine.

See :doc:`Best Practices <reference/best-practices>`

Change Tracking policies
------------------------

See: :doc:`Change Tracking Policies <change-tracking-policies>`
