Configuration
=============

Bootstrapping
-------------

Bootstrapping Doctrine is a relatively simple procedure that
roughly exists of just 2 steps:


-  Making sure Doctrine class files can be loaded on demand.
-  Obtaining an EntityManager instance.

Class loading
~~~~~~~~~~~~~

Lets start with the class loading setup. We need to set up some
class loaders (often called "autoloader") so that Doctrine class
files are loaded on demand. The Doctrine namespace contains a very
fast and minimalistic class loader that can be used for Doctrine
and any other libraries where the coding standards ensure that a
class's location in the directory tree is reflected by its name and
namespace and where there is a common root namespace.

.. note::

    You are not forced to use the Doctrine class loader to
    load Doctrine classes. Doctrine does not care how the classes are
    loaded, if you want to use a different class loader or your own to
    load Doctrine classes, just do that. Along the same lines, the
    class loader in the Doctrine namespace is not meant to be only used
    for Doctrine classes, too. It is a generic class loader that can be
    used for any classes that follow some basic naming standards as
    described above.


The following example shows the setup of a ``ClassLoader`` for the
different types of Doctrine Installations:

.. note::

    This assumes you've created some kind of script to test
    the following code in. Something like a ``test.php`` file.


PEAR or Tarball Download
^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

    <?php
    // test.php
    
    require '/path/to/libraries/Doctrine/Common/ClassLoader.php';
    $classLoader = new \Doctrine\Common\ClassLoader('Doctrine', '/path/to/libraries');
    $classLoader->register(); // register on SPL autoload stack

Git
^^^

The Git bootstrap assumes that you have fetched the related
packages through ``git submodule update --init``

.. code-block:: php

    <?php
    // test.php
    
    $lib = '/path/to/doctrine2-orm/lib/';
    require $lib . 'vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';
    
    $classLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', $lib . 'vendor/doctrine-common/lib');
    $classLoader->register();
    
    $classLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL', $lib . 'vendor/doctrine-dbal/lib');
    $classLoader->register();
    
    $classLoader = new \Doctrine\Common\ClassLoader('Doctrine\ORM', $lib);
    $classLoader->register();

Additional Symfony Components
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you don't use Doctrine2 in combination with Symfony2 you have to
register an additional namespace to be able to use the Doctrine-CLI
Tool or the YAML Mapping driver:

.. code-block:: php

    <?php
    // PEAR or Tarball setup
    $classloader = new \Doctrine\Common\ClassLoader('Symfony', '/path/to/libraries/Doctrine');
    $classloader->register();
    
    // Git Setup
    $classloader = new \Doctrine\Common\ClassLoader('Symfony', $lib . 'vendor/');
    $classloader->register();

For best class loading performance it is recommended that you keep
your include\_path short, ideally it should only contain the path
to the PEAR libraries, and any other class libraries should be
registered with their full base path.

Obtaining an EntityManager
~~~~~~~~~~~~~~~~~~~~~~~~~~

Once you have prepared the class loading, you acquire an
*EntityManager* instance. The EntityManager class is the primary
access point to ORM functionality provided by Doctrine.

A simple configuration of the EntityManager requires a
``Doctrine\ORM\Configuration`` instance as well as some database
connection parameters:

.. code-block:: php

    <?php
    use Doctrine\ORM\EntityManager,
        Doctrine\ORM\Configuration;
    
    // ...
    
    if ($applicationMode == "development") {
        $cache = new \Doctrine\Common\Cache\ArrayCache;
    } else {
        $cache = new \Doctrine\Common\Cache\ApcCache;
    }
    
    $config = new Configuration;
    $config->setMetadataCacheImpl($cache);
    $driverImpl = $config->newDefaultAnnotationDriver('/path/to/lib/MyProject/Entities');
    $config->setMetadataDriverImpl($driverImpl);
    $config->setQueryCacheImpl($cache);
    $config->setProxyDir('/path/to/myproject/lib/MyProject/Proxies');
    $config->setProxyNamespace('MyProject\Proxies');
    
    if ($applicationMode == "development") {
        $config->setAutoGenerateProxyClasses(true);
    } else {
        $config->setAutoGenerateProxyClasses(false);
    }
    
    $connectionOptions = array(
        'driver' => 'pdo_sqlite',
        'path' => 'database.sqlite'
    );
    
    $em = EntityManager::create($connectionOptions, $config);

.. note::

    Do not use Doctrine without a metadata and query cache!
    Doctrine is highly optimized for working with caches. The main
    parts in Doctrine that are optimized for caching are the metadata
    mapping information with the metadata cache and the DQL to SQL
    conversions with the query cache. These 2 caches require only an
    absolute minimum of memory yet they heavily improve the runtime
    performance of Doctrine. The recommended cache driver to use with
    Doctrine is `APC <http://www.php.net/apc>`_. APC provides you with
    an opcode-cache (which is highly recommended anyway) and a very
    fast in-memory cache storage that you can use for the metadata and
    query caches as seen in the previous code snippet.


Configuration Options
---------------------

The following sections describe all the configuration options
available on a ``Doctrine\ORM\Configuration`` instance.

Proxy Directory (***REQUIRED***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $config->setProxyDir($dir);
    $config->getProxyDir();

Gets or sets the directory where Doctrine generates any proxy
classes. For a detailed explanation on proxy classes and how they
are used in Doctrine, refer to the "Proxy Objects" section further
down.

Proxy Namespace (***REQUIRED***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $config->setProxyNamespace($namespace);
    $config->getProxyNamespace();

Gets or sets the namespace to use for generated proxy classes. For
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
-  ``Doctrine\ORM\Mapping\Driver\YamlDriver``
-  ``Doctrine\ORM\Mapping\Driver\DriverChain``

Throughout the most part of this manual the AnnotationDriver is
used in the examples. For information on the usage of the XmlDriver
or YamlDriver please refer to the dedicated chapters
``XML Mapping`` and ``YAML Mapping``.

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
annotations, xml or yaml, so that they do not need to be parsed and
loaded from scratch on every single request which is a waste of
resources. The cache implementation must implement the
``Doctrine\Common\Cache\Cache`` interface.

Usage of a metadata cache is highly recommended.

The recommended implementations for production are:


-  ``Doctrine\Common\Cache\ApcCache``
-  ``Doctrine\Common\Cache\MemcacheCache``
-  ``Doctrine\Common\Cache\XcacheCache``

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


-  ``Doctrine\Common\Cache\ApcCache``
-  ``Doctrine\Common\Cache\MemcacheCache``
-  ``Doctrine\Common\Cache\XcacheCache``

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

Auto-generating Proxy Classes (***OPTIONAL***)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $config->setAutoGenerateProxyClasses($bool);
    $config->getAutoGenerateProxyClasses();

Gets or sets whether proxy classes should be generated
automatically at runtime by Doctrine. If set to ``FALSE``, proxy
classes must be generated manually through the doctrine command
line task ``generate-proxies``. The strongly recommended value for
a production environment is ``FALSE``.

Development vs Production Configuration
---------------------------------------

You should code your Doctrine2 bootstrapping with two different
runtime models in mind. There are some serious benefits of using
APC or Memcache in production. In development however this will
frequently give you fatal errors, when you change your entities and
the cache still keeps the outdated metadata. That is why we
recommend the ``ArrayCache`` for development.

Furthermore you should have the Auto-generating Proxy Classes
option to true in development and to false in production. If this
option is set to ``TRUE`` it can seriously hurt your script
performance if several proxy classes are re-generated during script
execution. Filesystem calls of that magnitude can even slower than
all the database queries Doctrine issues. Additionally writing a
proxy sets an exclusive file lock which can cause serious
performance bottlenecks in systems with regular concurrent
requests.

Connection Options
------------------

The ``$connectionOptions`` passed as the first argument to
``EntityManager::create()`` has to be either an array or an
instance of ``Doctrine\DBAL\Connection``. If an array is passed it
is directly passed along to the DBAL Factory
``Doctrine\DBAL\DriverManager::getConnection()``. The DBAL
configuration is explained in the
`DBAL section <./../../../../../dbal/2.0/docs/reference/configuration/en>`_.

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
    $item = $em->getReference('MyProject\Model\Item', $itemId);
    $cart->addItem($item);

Here, we added an Item to a Cart without loading the Item from the
database. If you invoke any method on the Item instance, it would
fully initialize its state transparently from the database. Here
$item is actually an instance of the proxy class that was generated
for the Item class but your code does not need to care. In fact it
**should not care**. Proxy objects should be transparent to your
code.

Association proxies
~~~~~~~~~~~~~~~~~~~

The second most important situation where Doctrine uses proxy
objects is when querying for objects. Whenever you query for an
object that has a single-valued association to another object that
is configured LAZY, without joining that association in the same
query, Doctrine puts proxy objects in place where normally the
associated object would be. Just like other proxies it will
transparently initialize itself on first access.

.. note::

    Joining an association in a DQL or native query
    essentially means eager loading of that association in that query.
    This will override the 'fetch' option specified in the mapping for
    that association, but only for that query.


Generating Proxy classes
~~~~~~~~~~~~~~~~~~~~~~~~

Proxy classes can either be generated manually through the Doctrine
Console or automatically by Doctrine. The configuration option that
controls this behavior is:

.. code-block:: php

    <?php
    $config->setAutoGenerateProxyClasses($bool);
    $config->getAutoGenerateProxyClasses();

The default value is ``TRUE`` for convenient development. However,
this setting is not optimal for performance and therefore not
recommended for a production environment. To eliminate the overhead
of proxy class generation during runtime, set this configuration
option to ``FALSE``. When you do this in a development environment,
note that you may get class/file not found errors if certain proxy
classes are not available or failing lazy-loads if new methods were
added to the entity class that are not yet in the proxy class. In
such a case, simply use the Doctrine Console to (re)generate the
proxy classes like so:

.. code-block:: php

    $ ./doctrine orm:generate-proxies

Multiple Metadata Sources
-------------------------

When using different components using Doctrine 2 you may end up
with them using two different metadata drivers, for example XML and
YAML. You can use the DriverChain Metadata implementations to
aggregate these drivers based on namespaces:

.. code-block:: php

    <?php
    $chain = new DriverChain();
    $chain->addDriver($xmlDriver, 'Doctrine\Tests\Models\Company');
    $chain->addDriver($yamlDriver, 'Doctrine\Tests\ORM\Mapping');

Based on the namespace of the entity the loading of entities is
delegated to the appropriate driver. The chain semantics come from
the fact that the driver loops through all namespaces and matches
the entity class name against the namespace using a
``strpos() === 0`` call. This means you need to order the drivers
correctly if sub-namespaces use different metadata driver
implementations.


