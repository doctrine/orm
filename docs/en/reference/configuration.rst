Installation and Configuration
==============================

Doctrine can be installed with `Composer <https://getcomposer.org>`_.

Define the following requirement in your ``composer.json`` file:

::

    {
        "require": {
            "doctrine/orm": "*"
        }
    }

Then call ``composer install`` from your command line. If you don't know
how Composer works, check out their `Getting Started <https://getcomposer.org/doc/00-intro.md>`_ to set up.

Class loading
-------------

Autoloading is taken care of by Composer. You just have to include the composer autoload file in your project:

.. code-block:: php

    <?php
    // bootstrap.php
    // Include Composer Autoload (relative to project root).
    require_once "vendor/autoload.php";

Obtaining an EntityManager
--------------------------

Once you have prepared the class loading, you acquire an
*EntityManager* instance. The EntityManager class is the primary
access point to ORM functionality provided by Doctrine.

.. code-block:: php

    <?php
    // bootstrap.php
    require_once "vendor/autoload.php";

    use Doctrine\DBAL\DriverManager;
    use Doctrine\ORM\EntityManager;
    use Doctrine\ORM\ORMSetup;

    $paths = ['/path/to/entity-files'];
    $isDevMode = false;

    // the connection configuration
    $dbParams = [
        'driver'   => 'pdo_mysql',
        'user'     => 'root',
        'password' => '',
        'dbname'   => 'foo',
    ];

    $config = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode);
    $connection = DriverManager::getConnection($dbParams, $config);
    $entityManager = new EntityManager($connection, $config);

.. note::

    The ``ORMSetup`` class has been introduced with ORM 2.12. It's predecessor ``Setup`` is deprecated and will
    be removed in version 3.0.

Or if you prefer XML:

.. code-block:: php

    <?php
    $paths = ['/path/to/xml-mappings'];
    $config = ORMSetup::createXMLMetadataConfiguration($paths, $isDevMode);
    $connection = DriverManager::getConnection($dbParams, $config);
    $entityManager = new EntityManager($connection, $config);

Or if you prefer YAML:

.. code-block:: php

    <?php
    $paths = ['/path/to/yml-mappings'];
    $config = ORMSetup::createYAMLMetadataConfiguration($paths, $isDevMode);
    $connection = DriverManager::getConnection($dbParams, $config);
    $entityManager = new EntityManager($connection, $config);

.. note::
    If you want to use yml mapping you should add yaml dependency to your `composer.json`:

    ::

        "symfony/yaml": "*"

Inside the ``ORMSetup`` methods several assumptions are made:

-  If ``$isDevMode`` is true caching is done in memory with the ``ArrayAdapter``. Proxy objects are recreated on every request.
-  If ``$isDevMode`` is false, check for Caches in the order APCu, Redis (127.0.0.1:6379), Memcache (127.0.0.1:11211) unless `$cache` is passed as fourth argument.
-  If ``$isDevMode`` is false, set then proxy classes have to be explicitly created through the command line.
-  If third argument `$proxyDir` is not set, use the systems temporary directory.

.. note::

    In order to have ``ORMSetup`` configure the cache automatically, the library ``symfony/cache``
    has to be installed as a dependency.

If you want to configure Doctrine in more detail, take a look at the :doc:`Advanced Configuration <reference/advanced-configuration>` section.

.. note::

    You can learn more about the database connection configuration in the
    `Doctrine DBAL connection configuration reference <https://docs.doctrine-project.org/projects/doctrine-dbal/en/stable/reference/configuration.html>`_.

Setting up the Commandline Tool
-------------------------------

Doctrine ships with a number of command line tools that are very helpful
during development. In order to make use of them, create an executable PHP
script in your project as described in the
:doc:`tools chapter <../reference/tools>`.
