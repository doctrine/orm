Caching
=======

The Doctrine ORM package can leverage cache adapters implementing the PSR-6
standard to allow you to improve the performance of various aspects of
Doctrine by simply making some additional configurations and method calls.

.. _types-of-caches:

Types of Caches
---------------

Query Cache
~~~~~~~~~~~

It is highly recommended that in a production environment you cache
the transformation of a DQL query to its SQL counterpart. It
doesn't make sense to do this parsing multiple times as it doesn't
change unless you alter the DQL query.

This can be done by configuring the query cache implementation to
use on your ORM configuration.

.. code-block:: php

    <?php
    $cache = new \Symfony\Component\Cache\Adapter\PhpFilesAdapter('doctrine_queries');
    $config = new \Doctrine\ORM\Configuration();
    $config->setQueryCache($cache);

Result Cache
~~~~~~~~~~~~

The result cache can be used to cache the results of your queries
so that we don't have to query the database again after the first time.
You just need to configure the result cache implementation.

.. code-block:: php

    <?php
    $cache = new \Symfony\Component\Cache\Adapter\PhpFilesAdapter(
        'doctrine_results',
        0,
        '/path/to/writable/directory'
    );
    $config = new \Doctrine\ORM\Configuration();
    $config->setResultCache($cache);

Now when you're executing DQL queries you can configure them to use
the result cache.

.. code-block:: php

    <?php
    $query = $em->createQuery('select u from \Entities\User u');
    $query->enableResultCache();

You can also configure an individual query to use a different
result cache driver.

.. code-block:: php

    <?php
    $cache = new \Symfony\Component\Cache\Adapter\PhpFilesAdapter(
        'doctrine_results',
        0,
        '/path/to/writable/directory'
    );
    $query->setResultCache($cache);

.. note::

    Setting the result cache driver on the query will
    automatically enable the result cache for the query. If you want to
    disable it use ``disableResultCache()``.

    ::

        <?php
        $query->disableResultCache();


If you want to set the time the cache has to live you can use the
``setResultCacheLifetime()`` method.

.. code-block:: php

    <?php
    $query->setResultCacheLifetime(3600);

The ID used to store the result set cache is a hash which is
automatically generated for you if you don't set a custom ID
yourself with the ``setResultCacheId()`` method.

.. code-block:: php

    <?php
    $query->setResultCacheId('my_custom_id');

You can also set the lifetime and cache ID by passing the values as
the first and second argument to ``enableResultCache()``.

.. code-block:: php

    <?php
    $query->enableResultCache(3600, 'my_custom_id');

Metadata Cache
~~~~~~~~~~~~~~

Your class metadata can be parsed from a few different sources like
XML, Attributes, etc. Instead of parsing this
information on each request we should cache it using one of the cache
drivers.

Just like the query and result cache we need to configure it
first.

.. code-block:: php

    <?php
    $cache = \Symfony\Component\Cache\Adapter\PhpFilesAdapter(
        'doctrine_metadata',
        0,
        '/path/to/writable/directory'
    );
    $config = new \Doctrine\ORM\Configuration();
    $config->setMetadataCache($cache);

Now the metadata information will only be parsed once and stored in
the cache driver.

Clearing the Cache
------------------

We've already shown you how you can use the API of the
cache drivers to manually delete cache entries. For your
convenience we offer command line tasks to help you with
clearing the query, result and metadata cache.

From the Doctrine command line you can run the following commands:

To clear the query cache use the ``orm:clear-cache:query`` task.

.. code-block:: php

    $ ./doctrine orm:clear-cache:query

To clear the metadata cache use the ``orm:clear-cache:metadata`` task.

.. code-block:: php

    $ ./doctrine orm:clear-cache:metadata

To clear the result cache use the ``orm:clear-cache:result`` task.

.. code-block:: php

    $ ./doctrine orm:clear-cache:result

All these tasks accept a ``--flush`` option to flush the entire
contents of the cache instead of invalidating the entries.

.. note::

    None of these tasks will work with APC, APCu, or XCache drivers
    because the memory that the cache is stored in is only accessible
    to the webserver.

Cache Chaining
--------------

A common pattern is to use a static cache to store data that is
requested many times in a single PHP request. Even though this data
may be stored in a fast memory cache, often that cache is over a
network link leading to sizable network traffic.

A chain cache class allows multiple caches to be registered at once.
For example, a per-request array cache can be used first, followed by
a (relatively) slower Memcached cache if the array cache misses.
The chain cache automatically handles pushing data up to faster caches in
the chain and clearing data in the entire stack when it is deleted.

Symfony Cache provides such a chain cache. To find out how to use it,
please have a look at the
`Symfony Documentation <https://symfony.com/doc/current/components/cache/adapters/chain_adapter.html>`_.

Cache Slams
-----------

Something to be careful of when using the cache drivers is
"cache slams". Imagine you have a heavily trafficked website with some
code that checks for the existence of a cache record and if it does
not exist it generates the information and saves it to the cache.
Now, if 100 requests were issued all at the same time and each one
sees the cache does not exist and they all try to insert the same
cache entry it could lock up APC, Xcache, etc. and cause problems.
Ways exist to work around this, like pre-populating your cache and
not letting your users' requests populate the cache.

You can read more about cache slams
`in this blog post <http://notmysock.org/blog/php/user-cache-timebomb.html>`_.
