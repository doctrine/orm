Introduction
============

Welcome
-------

Doctrine 2 is an object-relational mapper (ORM) for PHP 5.3.0+ that
provides transparent persistence for PHP objects. It sits on top of
a powerful database abstraction layer (DBAL). Object-Relational
Mappers primary task is the transparent translation between (PHP)
objects and relational database rows.

One of Doctrines key features is the option to write database
queries in a proprietary object oriented SQL dialect called
Doctrine Query Language (DQL), inspired by Hibernates HQL. Besides
DQLs slight differences to SQL it abstracts the mapping between
database rows and objects considerably, allowing developers to
write powerful queries in a simple and flexible fashion.

Disclaimer
----------

This is the Doctrine 2 reference documentation. Introductory guides
and tutorials that you can follow along from start to finish, like
the "Guide to Doctrine" book known from the Doctrine 1.x series,
will be available at a later date.

Using an Object-Relational Mapper
---------------------------------

As the term ORM already hints at, Doctrine 2 aims to simplify the
translation between database rows and the PHP object model. The
primary use case for Doctrine are therefore applications that
utilize the Object-Oriented Programming Paradigm. For applications
that not primarily work with objects Doctrine 2 is not suited very
well.

Requirements
------------

Doctrine 2 requires a minimum of PHP 5.3.0. For greatly improved
performance it is also recommended that you use APC with PHP.

Doctrine 2 Packages
-------------------

Doctrine 2 is divided into three main packages.


-  Common
-  DBAL (includes Common)
-  ORM (includes DBAL+Common)

This manual mainly covers the ORM package, sometimes touching parts
of the underlying DBAL and Common packages. The Doctrine code base
is split in to these packages for a few reasons and they are to...


-  ...make things more maintainable and decoupled
-  ...allow you to use the code in Doctrine Common without the ORM
   or DBAL
-  ...allow you to use the DBAL without the ORM

The Common Package
~~~~~~~~~~~~~~~~~~

The Common package contains highly reusable components that have no
dependencies beyond the package itself (and PHP, of course). The
root namespace of the Common package is ``Doctrine\Common``.

The DBAL Package
~~~~~~~~~~~~~~~~

The DBAL package contains an enhanced database abstraction layer on
top of PDO but is not strongly bound to PDO. The purpose of this
layer is to provide a single API that bridges most of the
differences between the different RDBMS vendors. The root namespace
of the DBAL package is ``Doctrine\DBAL``.

The ORM Package
~~~~~~~~~~~~~~~

The ORM package contains the object-relational mapping toolkit that
provides transparent relational persistence for plain PHP objects.
The root namespace of the ORM package is ``Doctrine\ORM``.

Installing
----------

Doctrine can be installed many different ways. We will describe all
the different ways and you can choose which one suits you best.

PEAR
~~~~

You can easily install any of the three Doctrine packages from the
PEAR command line installation utility.

To install just the ``Common`` package you can run the following
command:

.. code-block:: bash

    $ sudo pear install pear.doctrine-project.org/DoctrineCommon-<version>

If you want to use the Doctrine Database Abstraction Layer you can
install it with the following command.

.. code-block:: bash

    $ sudo pear install pear.doctrine-project.org/DoctrineDBAL-<version>

Or, if you want to get the works and go for the ORM you can install
it with the following command.

.. code-block:: bash

    $ sudo pear install pear.doctrine-project.org/DoctrineORM-<version>


.. note::

    The ``<version>`` tag above represents the version you
    want to install. For example if the current version at the time of
    writing this is ``2.0.7`` for the ORM, so you could install it
    like the following:

    .. code-block:: bash

        $ sudo pear install pear.doctrine-project.org/DoctrineORM-2.0.7


When you have a package installed via PEAR you can require and load
the ``ClassLoader`` with the following code.

.. code-block:: php

    <?php
    require 'Doctrine/Common/ClassLoader.php';
    $classLoader = new \Doctrine\Common\ClassLoader('Doctrine');
    $classLoader->register();

The packages are installed in to your shared PEAR PHP code folder
in a folder named ``Doctrine``. You also get a nice command line
utility installed and made available on your system. Now when you
run the ``doctrine`` command you will see what you can do with it.

.. code-block:: php

    $ doctrine
    Doctrine Command Line Interface version 2.0.0BETA3-DEV
    
    Usage:
      [options] command [arguments]
    
    Options:
      --help           -h Display this help message.
      --quiet          -q Do not output any message.
      --verbose        -v Increase verbosity of messages.
      --version        -V Display this program version.
      --color          -c Force ANSI color output.
      --no-interaction -n Do not ask any interactive question.
    
    Available commands:
      help                         Displays help for a command (?)
      list                         Lists commands
    dbal
      :import                      Import SQL file(s) directly to Database.
      :run-sql                     Executes arbitrary SQL directly from the command line.
    orm
      :convert-d1-schema           Converts Doctrine 1.X schema into a Doctrine 2.X schema.
      :convert-mapping             Convert mapping information between supported formats.
      :ensure-production-settings  Verify that Doctrine is properly configured for a production environment.
      :generate-entities           Generate entity classes and method stubs from your mapping information.
      :generate-proxies            Generates proxy classes for entity classes.
      :generate-repositories       Generate repository classes from your mapping information.
      :run-dql                     Executes arbitrary DQL directly from the command line.
      :validate-schema             Validate that the mapping files.
    orm:clear-cache
      :metadata                    Clear all metadata cache of the various cache drivers.
      :query                       Clear all query cache of the various cache drivers.
      :result                      Clear result cache of the various cache drivers.
    orm:schema-tool
      :create                      Processes the schema and either create it directly on EntityManager Storage Connection or generate the SQL output.
      :drop                        Processes the schema and either drop the database schema of EntityManager Storage Connection or generate the SQL output.
      :update                      Processes the schema and either update the database schema of EntityManager Storage Connection or generate the SQL output.

Package Download
~~~~~~~~~~~~~~~~

You can also use Doctrine 2 by downloading the latest release
package from
`the download page <http://www.doctrine-project.org/download>`_.

See the configuration section on how to configure and bootstrap a
downloaded version of Doctrine.

GitHub
~~~~~~

Alternatively you can clone the latest version of Doctrine 2 via
GitHub.com:

.. code-block:: php

    $ git clone git://github.com/doctrine/doctrine2.git doctrine

This downloads all the sources of the ORM package. You need to
initialize the Github submodules for the Common and DBAL package
dependencies:

.. code-block:: php

    $ git submodule init
    $ git submodule update

This updates your Git checkout to use the Doctrine and Doctrine
package versions that are recommended for the cloned Master version
of Doctrine 2.

See the configuration chapter on how to configure a Github
installation of Doctrine with regards to autoloading.

    **NOTE**

    You should not combine the Doctrine-Common, Doctrine-DBAL and
    Doctrine-ORM master commits with each other in combination. The ORM
    may not work with the current Common or DBAL master versions.
    Instead the ORM ships with the Git Submodules that are required.


Subversion
~~~~~~~~~~

    **NOTE**

    Using the SVN Mirror is not recommended. It only allows access to
    the latest master commit and does not automatically fetch the
    submodules.


If you prefer subversion you can also checkout the code from
GitHub.com through the subversion protocol:

.. code-block:: php

    $ svn co http://svn.github.com/doctrine/doctrine2.git doctrine2

However this only allows you to check out the current master of
Doctrine 2, without the Common and DBAL dependencies. You have to
grab them yourself, but might run into version incompatibilities
between the different master branches of Common, DBAL and ORM.

Sandbox Quickstart
------------------

    **NOTE** The sandbox is only available via the Doctrine2 Github
    Repository or soon as a separate download on the downloads page.
    You will find it in the $root/tools/sandbox folder.


The sandbox is a pre-configured environment for evaluating and
playing with Doctrine 2.

Overview
~~~~~~~~

After navigating to the sandbox directory, you should see the
following structure:

.. code-block:: php

    sandbox/
        Entities/
            Address.php
            User.php
        xml/
            Entities.Address.dcm.xml
            Entities.User.dcm.xml
        yaml/
            Entities.Address.dcm.yml
            Entities.User.dcm.yml
        cli-config.php
        doctrine
        doctrine.php
        index.php

Here is a short overview of the purpose of these folders and
files:


-  The ``Entities`` folder is where any model classes are created.
   Two example entities are already there.
-  The ``xml`` folder is where any XML mapping files are created
   (if you want to use XML mapping). Two example mapping documents for
   the 2 example entities are already there.
-  The ``yaml`` folder is where any YAML mapping files are created
   (if you want to use YAML mapping). Two example mapping documents
   for the 2 example entities are already there.
-  The ``cli-config.php`` contains bootstrap code for a
   configuration that is used by the Console tool ``doctrine``
   whenever you execute a task.
-  ``doctrine``/``doctrine.php`` is a command-line tool.
-  ``index.php`` is a basic classical bootstrap file of a php
   application that uses Doctrine 2.

Mini-tutorial
~~~~~~~~~~~~~


1) From within the tools/sandbox folder, run the following command
   and you should see the same output.

   $ php doctrine orm:schema-tool:create Creating database schema...
   Database schema created successfully!

2) Take another look into the tools/sandbox folder. A SQLite
   database should have been created with the name
   ``database.sqlite``.

3) Open ``index.php`` and at the bottom edit it so it looks like
   the following:

   
.. code-block:: php

    <?php
    //... bootstrap stuff

    ## PUT YOUR TEST CODE BELOW

    $user = new \Entities\User;
    $user->setName('Garfield');
    $em->persist($user);
    $em->flush();

    echo "User saved!";


Open index.php in your browser or execute it on the command line.
You should see the output "User saved!".


4) Inspect the SQLite database. Again from within the tools/sandbox
   folder, execute the following command:

   $ php doctrine dbal:run-sql "select \* from users"


You should get the following output:

.. code-block:: php

    array(1) {
      [0]=>
      array(2) {
        ["id"]=>
        string(1) "1"
        ["name"]=>
        string(8) "Garfield"
      }
    }

You just saved your first entity with a generated ID in an SQLite
database.


5) Replace the contents of index.php with the following:

   
.. code-block:: php

    <?php
    //... bootstrap stuff

    ## PUT YOUR TEST CODE BELOW

    $q = $em->createQuery('select u from Entities\User u where u.name = ?1');
    $q->setParameter(1, 'Garfield');
    $garfield = $q->getSingleResult();

    echo "Hello " . $garfield->getName() . "!";


You just created your first DQL query to retrieve the user with the
name 'Garfield' from an SQLite database (Yes, there is an easier
way to do it, but we wanted to introduce you to DQL at this point.
Can you **find** the easier way?).

    **TIP** When you create new model classes or alter existing ones
    you can recreate the database schema with the command
    ``doctrine orm:schema-tool --drop`` followed by
    ``doctrine orm:schema-tool --create``.



6) Explore Doctrine 2!

Instead of reading through the reference manual we also recommend to look at the tutorials:

:doc:`Getting Started Tutorial <../tutorials/getting-started-xml-edition>`


