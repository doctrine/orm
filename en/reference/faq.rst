Frequently Asked Questions
==========================

.. note::

    This FAQ is a work in progress. We will add lots of questions and not answer them right away just to remember
    what is often asked. If you stumble accross an unanswerd question please write a mail to the mailing-list or
    join the #doctrine channel on Freenode IRC.

Entity Classes
--------------

I access a variable and its null, what is wrong?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Proxy, not use public. Private and protected variables instead.

How can I add default values to a column?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Doctrine does not support to set the default values in columns through the "DEFAULT" keyword in SQL.
This is not necessary however, you can just use your class properties as default values. These are then used
upon insert:

.. code-block::

    class User
    {
        const STATUS_DISABLED = 0;
        const STATUS_ENABLED = 1;

        private $algorithm = "sha1";
        private $status = self:STATUS_DISABLED;
    }

.

Mapping
-------

Why do I get exceptions about unique constraint failures during ``$em->flush()``?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

-

Associations
------------

What is wrong when I get an InvalidArgumentException "A new entity was found through the relationship.."?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~


How can I filter an association?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Natively you can't in 2.0 and 2.1. You should use DQL queries to query for the filtered set of entities.

I call clear() on a One-To-Many collection but the entities are not deleted
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.

How can I add columns to a many-to-many table?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.


How can i paginate fetch-joined collections?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.

Why does pagination not work correctly with fetch joins?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.

Inheritance
-----------

Can I use Inheritance with Doctrine 2?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 
.

EntityGenerator
---------------

Why does the EntityGenerator not do X?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.

Why does the EntityGenerator not generate inheritance correctly?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.



.

Performance
-----------

Why is an extra SQL query executed every time I fetch an entity with a one-to-one relation?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.

Doctrine Query Language
-----------------------

What is DQL and why does it have such a strange syntax?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Can I sort by a function (for example ORDER BY RAND()) in DQL?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

No, it is not supported to sort by function in DQL. If you need this functionality you should either
use a native-query or come up with another solution. As a side note: Sorting with ORDER BY RAND() is painfully slow
starting with 1000 rows.

