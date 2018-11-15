Advanced Configuration
======================

The configuration of the EntityManager requires a
``Doctrine\ORM\Configuration`` instance as well as some database
connection parameters. This example shows all the potential
steps of configuration.

.. code-block:: php

    <?php
    use Doctrine\ORM\EntityManager;
    use Doctrine\ORM\Configuration;
    use Doctrine\Common\Proxy\ProxyFactory;

    // ...

    if ($applicationMode == "development") {
        $cache = new \Doctrine\Common\Cache\ArrayCache;
    } else {
        $cache = new \Doctrine\Common\Cache\ApcuCache;
    }

    $config = new Configuration;
    $config->setMetadataCacheImpl($cache);
    $driverImpl = $config->newDefaultAnnotationDriver('/path/to/lib/MyProject/Entities');
    $config->setMetadataDriverImpl($driverImpl);
    $config->setQueryCacheImpl($cache);
    $config->setProxyDir('/path/to/myproject/lib/MyProject/Proxies');
    $config->setProxyNamespace('MyProject\Proxies');
    $config->setAutoGenerateProxyClasses($applicationMode === 'development')

    if ('development' === $applicationMode) {
        $config->setAutoGenerateProxyClasses(ProxyFactory::AUTOGENERATE_EVAL);
    }

    $connectionOptions = [
        'driver' => 'pdo_sqlite',
        'path' => 'database.sqlite'
    ];

    $em = EntityManager::create($connectionOptions, $config);

.. note::

    Do not use Doctrine without a metadata and query cache!
    Doctrine is optimized for working with caches. The main
    parts in Doctrine that are optimized for caching are the metadata
    mapping information with the metadata cache and the DQL to SQL
    conversions with the query cache. These 2 caches require only an
    absolute minimum of memory yet they heavily improve the runtime
    performance of Doctrine. The recommended cache driver to use with
    Doctrine is `APCu <https://php.net/apcu>`_. APCu provides you with
    a very fast in-memory cache storage that you can use for the metadata and
    query caches as seen in the previous code snippet.

Configuration Options
---------------------

The following sections describe all the configuration options
available on a ``Doctrine\ORM\Configuration`` instance.

Proxy Directory
~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $config->setProxyDir($dir);

Sets the directory where Doctrine generates any proxy
classes. For a detailed explanation on proxy classes and how they
are used in Doctrine, refer to the "Proxy Objects" section further
down.

Setting the proxy target directory will also implicitly cause a
call to ``Doctrine\ORM\Configuration#setAutoGenerateProxyClasses()``
with a value of ``Doctrine\Common\Proxy\ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS``.

Proxy Namespace
~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $config->setProxyNamespace($namespace);

Sets the namespace to use for generated proxy classes. For
a detailed explanation on proxy classes and how they are used in
Doctrine, refer to the "Proxy Objects" section further down.

Metadata Driver (***REQUIRED***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $config->setMetadataDriverImpl($driver);
    $config->getMetadataDriverImpl();

Gets or sets the metadata driver implementation that is used by
Doctrine to acquire the object-relational metadata for your
classes.

There are currently 4 available implementations:

-  ``Doctrine\ORM\Mapping\Driver\AnnotationDriver``
-  ``Doctrine\ORM\Mapping\Driver\XmlDriver``
-  ``Doctrine\ORM\Mapping\Driver\DriverChain``

Throughout the most part of this manual the AnnotationDriver is
used in the examples. For information on the usage of the XmlDriver
please refer to the dedicated chapters ``XML Mapping``.

The annotation driver can be configured with a factory method on
the ``Doctrine\ORM\Configuration``:

.. code-block:: php

    <?php
    $driverImpl = $config->newDefaultAnnotationDriver('/path/to/lib/MyProject/Entities');
    $config->setMetadataDriverImpl($driverImpl);

The path information to the entities is required for the annotation
driver, because otherwise mass-operations on all entities through
the console could not work correctly. All of metadata drivers
accept either a single directory as a string or an array of
directories. With this feature a single driver can support multiple
directories of Entities.

Metadata Cache (***RECOMMENDED***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $config->setMetadataCacheImpl($cache);
    $config->getMetadataCacheImpl();

Gets or sets the cache implementation to use for caching metadata
information, that is, all the information you supply via
annotations or xml, so that they do not need to be parsed and
loaded from scratch on every single request which is a waste of
resources. The cache implementation must implement the
``Doctrine\Common\Cache\Cache`` interface.

Usage of a metadata cache is highly recommended.

The recommended implementations for production are:

-  ``Doctrine\Common\Cache\ApcuCache``
-  ``Doctrine\Common\Cache\MemcacheCache``
-  ``Doctrine\Common\Cache\XcacheCache``
-  ``Doctrine\Common\Cache\RedisCache``

For development you should use the
``Doctrine\Common\Cache\ArrayCache`` which only caches data on a
per-request basis.

Query Cache (***RECOMMENDED***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $config->setQueryCacheImpl($cache);
    $config->getQueryCacheImpl();

Gets or sets the cache implementation to use for caching DQL
queries, that is, the result of a DQL parsing process that includes
the final SQL as well as meta information about how to process the
SQL result set of a query. Note that the query cache does not
affect query results. You do not get stale data. This is a pure
optimization cache without any negative side-effects (except some
minimal memory usage in your cache).

Usage of a query cache is highly recommended.

The recommended implementations for production are:

-  ``Doctrine\Common\Cache\ApcuCache``
-  ``Doctrine\Common\Cache\MemcacheCache``
-  ``Doctrine\Common\Cache\XcacheCache``
-  ``Doctrine\Common\Cache\RedisCache``

For development you should use the
``Doctrine\Common\Cache\ArrayCache`` which only caches data on a
per-request basis.

SQL Logger (***Optional***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $config->setSQLLogger($logger);
    $config->getSQLLogger();

Gets or sets the logger to use for logging all SQL statements
executed by Doctrine. The logger class must implement the
``Doctrine\DBAL\Logging\SQLLogger`` interface. A simple default
implementation that logs to the standard output using ``echo`` and
``var_dump`` can be found at
``Doctrine\DBAL\Logging\EchoSQLLogger``.

Auto-generating Proxy Classes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Proxy classes can either be generated manually through the Doctrine
Console or automatically at runtime by Doctrine. The configuration
option that controls this behavior is:

.. code-block:: php

    <?php
    $config->setAutoGenerateProxyClasses($mode);

Possible values for ``$mode`` are:

-  ``Doctrine\Common\Proxy\ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS``

Generate the proxy class when the proxy file does not exist.
This strategy can potentially cause disk access.
Note that autoloading will be attempted before falling back
to generating a proxy class: if an already existing proxy class
is found, then no file write operations will be performed.

-  ``Doctrine\Common\Proxy\ProxyFactory::AUTOGENERATE_EVAL``

Generate the proxy classes and evaluate them on the fly via ``eval()``,
avoiding writing the proxies to disk.
This strategy is only sane for development and long running
processes.

-  ``Doctrine\Common\Proxy\ProxyFactory::AUTOGENERATE_NEVER``

This flag is deprecated, and is an alias
of ``Doctrine\Common\Proxy\ProxyFactory::AUTOGENERATE_EVAL``

-  ``Doctrine\Common\Proxy\ProxyFactory::AUTOGENERATE_ALWAYS``

This flag is deprecated, and is an alias
of ``Doctrine\Common\Proxy\ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS``

Before v2.4, ``setAutoGenerateProxyClasses`` would accept a boolean
value. This is still possible, ``FALSE`` being equivalent to
AUTOGENERATE_NEVER and ``TRUE`` to AUTOGENERATE_ALWAYS.

Manually generating Proxy Classes for performance
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

While the ORM can generate proxy classes when required, it is suggested
to not let this happen for production environments, as it has a major
impact on your application's performance.

In a production environment, it is highly recommended to use
``Doctrine\Common\Proxy\ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS``
in combination with a well-configured
`composer class autoloader<https://getcomposer.org/doc/01-basic-usage.md#autoloading>`_.

Here is an example of such setup:

.. code-block:: json

    {
        "autoload": {
            "psr-4": {
                "MyProject\\": "path/to/project/sources/",
                "GeneratedProxies\\": "path/to/generated/proxies/"
            }
        }
    }

You would then configure the ORM to use the ``"GeneratedProxies"``
and the ``"path/to/generated/proxies/"`` for the proxy classes:

.. code-block:: php

    <?php
    $config->setProxyDir('path/to/generated/proxies/');
    $config->setProxyNamespace('GeneratedProxies');

To make sure proxies are never generated by Doctrine, you'd forcefully
generate them during deployment operations:

.. code-block:: sh

    $ ./vendor/bin/doctrine orm:generate-proxies
    $ composer dump-autoload

Development vs Production Configuration
---------------------------------------

You should code your Doctrine2 bootstrapping with two different
runtime models in mind. There are some serious benefits of using
APCu or Memcache in production. In development however this will
frequently give you fatal errors, when you change your entities and
the cache still keeps the outdated metadata. That is why we
recommend the ``ArrayCache`` for development.

Furthermore you should disable the Auto-generating Proxy Classes
option in production.

Connection Options
------------------

The ``$connectionOptions`` passed as the first argument to
``EntityManager::create()`` has to be either an array or an
instance of ``Doctrine\DBAL\Connection``. If an array is passed it
is directly passed along to the DBAL Factory
``Doctrine\DBAL\DriverManager::getConnection()``. The DBAL
configuration is explained in the
`DBAL section <./../../../../../projects/doctrine-dbal/en/latest/reference/configuration.html>`_.

Proxy Objects
-------------

A proxy object is an object that is put in place or used instead of
the "real" object. A proxy object can add behavior to the object
being proxied without that object being aware of it. In Doctrine 2,
proxy objects are used to realize several features but mainly for
transparent lazy-loading.

Proxy objects with their lazy-loading facilities help to keep the
subset of objects that are already in memory connected to the rest
of the objects. This is an essential property as without it there
would always be fragile partial objects at the outer edges of your
object graph.

Doctrine 2 implements a variant of the proxy pattern where it
generates classes that extend your entity classes and adds
lazy-loading capabilities to them. Doctrine can then give you an
instance of such a proxy class whenever you request an object of
the class being proxied. This happens in two situations:

Reference Proxies
~~~~~~~~~~~~~~~~~

The method ``EntityManager#getReference($entityName, $identifier)``
lets you obtain a reference to an entity for which the identifier
is known, without loading that entity from the database. This is
useful, for example, as a performance enhancement, when you want to
establish an association to an entity for which you have the
identifier. You could simply do this:

.. code-block:: php

    <?php
    // $em instanceof EntityManager, $cart instanceof MyProject\Model\Cart
    // $itemId comes from somewhere, probably a request parameter
    $item = $em->getReference(\MyProject\Model\Item::class, $itemId);
    $cart->addItem($item);

Here, we added an ``Item`` to a ``Cart`` without loading the Item from the
database.
If you access any persistent state that isn't yet available in the ``Item``
instance, the proxying mechanism would fully initialize the object's state
transparently from the database.
Here ``$item`` is actually an instance of the proxy class that was generated
for the ``Item`` class but your code does not need to care. In fact it
**should not care**. Proxy objects should be transparent to your code.

Association proxies
~~~~~~~~~~~~~~~~~~~

The second most important situation where Doctrine uses proxy
objects is when querying for objects. Whenever you query for an
object that has a single-valued association to another object that
is configured ``LAZY``, without joining that association in the same
query, Doctrine puts proxy objects in place where normally the
associated object would be. Just like other proxies it will
transparently initialize itself on first access.

.. note::

    Joining an association in a DQL or native query
    essentially means eager loading of that association in that query.
    This will override the 'fetch' option specified in the mapping for
    that association, but only for that query.

Multiple Metadata Sources
-------------------------

When using different components using Doctrine 2 you may end up
with them using two different metadata drivers, for example XML and
annotationsL. You can use the DriverChain Metadata implementations to
aggregate these drivers based on namespaces:

.. code-block:: php

    <?php
    use Doctrine\ORM\Mapping\Driver\DriverChain;

    $chain = new DriverChain();
    $chain->addDriver($xmlDriver, 'Doctrine\Tests\Models\Company');
    $chain->addDriver($annotationDriver, 'Doctrine\Tests\ORM\Mapping');

Based on the namespace of the entity the loading of entities is
delegated to the appropriate driver. The chain semantics come from
the fact that the driver loops through all namespaces and matches
the entity class name against the namespace using a
``strpos() === 0`` call. This means you need to order the drivers
correctly if sub-namespaces use different metadata driver
implementations.

Default Repository (***OPTIONAL***)
-----------------------------------

Specifies the FQCN of a subclass of the EntityRepository.
That will be available for all entities without a custom repository class.

.. code-block:: php

    <?php
    $config->setDefaultRepositoryClassName($fqcn);
    $config->getDefaultRepositoryClassName();

The default value is ``Doctrine\ORM\EntityRepository``.
Any repository class must be a subclass of EntityRepository otherwise you got an ORMException

Setting up the Console
----------------------

Doctrine uses the Symfony Console component for generating the command
line interface. You can take a look at the ``vendor/bin/doctrine.php``
script and the ``Doctrine\ORM\Tools\Console\ConsoleRunner`` command
for inspiration how to setup the cli.

In general the required code looks like this:

.. code-block:: php

    <?php
    $cli = new Application('Doctrine Command Line Interface', \Doctrine\ORM\Version::VERSION);
    $cli->setCatchExceptions(true);
    $cli->setHelperSet($helperSet);
    Doctrine\ORM\Tools\Console\ConsoleRunner::addCommands($cli);
    $cli->run();

