Installation
============

Doctrine was installable in many different ways, however `Composer <http://www.getcomposer.org>`_ turned out to be one of the best things for PHP in a long time.
This is why we moved all installation to use Composer only. 

Define the following requirement in your ``composer.json`` file:

::

    {
        "require": {
            "doctrine/orm": "*"
        }
    }

Then run the composer command and you are done. Continue with the
:doc:`Configuration <configuration>`.
