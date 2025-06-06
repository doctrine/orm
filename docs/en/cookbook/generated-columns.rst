Generated Columns
=================

Generated columns, sometimes also called virtual columns, are populated by
the database engine itself. They are a tool for performance optimization, to
avoid calculating a value on each query.

You can define generated columns on entities and have Doctrine map the values
to your entity.

Declaring a generated column
----------------------------

There is no explicit mapping instruction for generated columns. Instead, you
specify that the column should not be written to, and define a custom column
definition.

.. literalinclude:: generated-columns/Person.php
   :language: php

* ``insertable``, ``updatable``: Setting these to false tells Doctrine to never
  write this column - writing to a generated column would result in an error
  from the database.
* ``columnDefinition``: We specify the full DDL to create the column. To allow
  to use database specific features, this attribute does not use Doctrine Query
  Language but native SQL. Note that you need to reference columns by their
  database name (either explicitly set in the mapping or per the current
  :doc:`naming strategy <../reference/namingstrategy>`).
  Be aware that specifying a column definition makes the ``SchemaTool``
  completely ignore all other configuration for this column. See also
  :ref:`#[Column] <attrref_column>`
* ``generated``: Specifying that this column is always generated tells Doctrine
  to update the field on the entity with the value from the database after
  every write operation.

Advanced example: Extracting a value from a JSON structure
----------------------------------------------------------

Lets assume we have an entity that stores a blogpost as structured JSON.
To avoid extracting all titles on the fly when listing the posts, we create a
generated column with the field.

.. literalinclude:: generated-columns/Article.php
   :language: php
