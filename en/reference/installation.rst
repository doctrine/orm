Installation
============

Doctrine can be installed many different ways. We will describe all the different ways and you can choose which one suits you best.

Composer
--------

`Composer <http://www.getcomposer.org>`_ is the suggested installation method for Doctrine.
Define the following requirement in your ``composer.json`` file:

::

    {
        "require": {
            "doctrine/orm": "*"
        }
    }

Then run the composer command and you are done. Continue with the
:doc:`Configuration <configuration>`.

PEAR
----

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
    writing this is ``2.2.2`` for the ORM, so you could install it
    like the following:

    .. code-block:: bash

        $ sudo pear install pear.doctrine-project.org/DoctrineORM-2.2.2

GitHub
------

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

.. note::

    You should not combine the Doctrine-Common, Doctrine-DBAL and
    Doctrine-ORM master commits with each other in combination. The ORM
    may not work with the current Common or DBAL master versions.
    Instead the ORM ships with the Git Submodules that are required.
