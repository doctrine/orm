Getting Started: Database First
===============================

.. note:: *Development Workflows*

    When you :doc:`Code First <getting-started>`, you
    start with developing Objects and then map them onto your database. When
    you :doc:`Model First <getting-started-models>`, you are modelling your application using tools (for
    example UML) and generate database schema and PHP code from this model.
    When you have a Database First, you already have a database schema
    and generate the corresponding PHP code from it.

.. note::

    This getting started guide is in development.

Development of new applications often starts with an existing database schema.
When the database schema is the starting point for your application, then
development is said to use the *Database First* approach to Doctrine.

In this workflow you would modify the database schema first and then
regenerate the PHP code to use with this schema. You need a flexible
code-generator for this task.

We spinned off a subproject, Doctrine CodeGenerator, that will fill this gap and
allow you to do *Database First* development.
