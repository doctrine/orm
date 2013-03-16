Installation and Configuration
==============================

Doctrine can be installed with `Composer <http://www.getcomposer.org>`_.  For
older versions we still have `PEAR packages
<http://pear.doctrine-project.org>`_.

Define the following requirement in your ``composer.json`` file:

::

    {
        "require": {
            "doctrine/orm": "*"
        }
    }

Then call ``composer install`` from your command line. If you don't know
how Composer works, check out their `Getting Started
<http://getcomposer.org/doc/00-intro.md>`_ to set up.

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

    use Doctrine\ORM\Tools\Setup;
    use Doctrine\ORM\EntityManager;

    $paths = array("/path/to/entities-or-mapping-files");
    $isDevMode = false;

    // the connection configuration
    $dbParams = array(
        'driver'   => 'pdo_mysql',
        'user'     => 'root',
        'password' => '',
        'dbname'   => 'foo',
    );

    $config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
    $entityManager = EntityManager::create($dbParams, $config);

Or if you prefer XML:

.. code-block:: php

    <?php
    $config = Setup::createXMLMetadataConfiguration($paths, $isDevMode);
    $entityManager = EntityManager::create($dbParams, $config);

Or if you prefer YAML:

.. code-block:: php

    <?php
    $config = Setup::createYAMLMetadataConfiguration($paths, $isDevMode);
    $entityManager = EntityManager::create($dbParams, $config);

Inside the ``Setup`` methods several assumptions are made:

-  If `$devMode` is true always use an ``ArrayCache`` and set ``setAutoGenerateProxyClasses(true)``.
-  If `$devMode` is false, check for Caches in the order APC, Xcache, Memcache (127.0.0.1:11211), Redis (127.0.0.1:6379) unless `$cache` is passed as fourth argument.
-  If `$devMode` is false, set ``setAutoGenerateProxyClasses(false)``
-  If third argument `$proxyDir` is not set, use the systems temporary directory.

If you want to configure Doctrine in more detail, take a look at the `Advanced
Configuration <reference/advanced-configuration>` section.

.. note::

    You can learn more about the database connection configuration in the
    `Doctrine DBAL connection configuration reference <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html>`_.

