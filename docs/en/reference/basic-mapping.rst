Basic Mapping
=============

This chapter explains the basic mapping of objects and properties.
Mapping of associations will be covered in the next chapter
"Association Mapping".

Mapping Drivers
---------------

Doctrine provides several different ways for specifying
object-relational mapping metadata:


-  Docblock Annotations
-  XML
-  YAML

This manual usually mentions docblock annotations in all the examples
that are spread throughout all chapters, however for many examples
alternative YAML and XML examples are given as well. There are dedicated
reference chapters for XML and YAML mapping, respectively that explain them
in more detail. There is also an Annotation reference chapter.

.. note::

    If you're wondering which mapping driver gives the best
    performance, the answer is: They all give exactly the same performance.
    Once the metadata of a class has
    been read from the source (annotations, xml or yaml) it is stored
    in an instance of the ``Doctrine\ORM\Mapping\ClassMetadata`` class
    and these instances are stored in the metadata cache. Therefore at
    the end of the day all drivers perform equally well. If you're not
    using a metadata cache (not recommended!) then the XML driver might
    have a slight edge in performance due to the powerful native XML
    support in PHP.


Introduction to Docblock Annotations
------------------------------------

You've probably used docblock annotations in some form already,
most likely to provide documentation metadata for a tool like
``PHPDocumentor`` (@author, @link, ...). Docblock annotations are a
tool to embed metadata inside the documentation section which can
then be processed by some tool. Doctrine 2 generalizes the concept
of docblock annotations so that they can be used for any kind of
metadata and so that it is easy to define new docblock annotations.
In order to allow more involved annotation values and to reduce the
chances of clashes with other docblock annotations, the Doctrine 2
docblock annotations feature an alternative syntax that is heavily
inspired by the Annotation syntax introduced in Java 5.

The implementation of these enhanced docblock annotations is
located in the ``Doctrine\Common\Annotations`` namespace and
therefore part of the Common package. Doctrine 2 docblock
annotations support namespaces and nested annotations among other
things. The Doctrine 2 ORM defines its own set of docblock
annotations for supplying object-relational mapping metadata.

.. note::

    If you're not comfortable with the concept of docblock
    annotations, don't worry, as mentioned earlier Doctrine 2 provides
    XML and YAML alternatives and you could easily implement your own
    favourite mechanism for defining ORM metadata.


Persistent classes
------------------

In order to mark a class for object-relational persistence it needs
to be designated as an entity. This can be done through the
``@Entity`` marker annotation.

.. configuration-block::

    .. code-block:: php

        <?php
        /** @Entity */
        class MyPersistentClass
        {
            //...
        }

    .. code-block:: xml

        <doctrine-mapping>
          <entity name="MyPersistentClass">
              <!-- ... -->
          </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
          type: entity
          # ...

By default, the entity will be persisted to a table with the same
name as the class name. In order to change that, you can use the
``@Table`` annotation as follows:

.. configuration-block::

    .. code-block:: php

        <?php
        /**
         * @Entity
         * @Table(name="my_persistent_class")
         */
        class MyPersistentClass
        {
            //...
        }

    .. code-block:: xml

        <doctrine-mapping>
          <entity name="MyPersistentClass" table="my_persistent_class">
              <!-- ... -->
          </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
          type: entity
          table: my_persistent_class
          # ...

Now instances of MyPersistentClass will be persisted into a table
named ``my_persistent_class``.

Doctrine Mapping Types
----------------------

A Doctrine Mapping Type defines the mapping between a PHP type and
a SQL type. All Doctrine Mapping Types that ship with Doctrine are
fully portable between different RDBMS. You can even write your own
custom mapping types that might or might not be portable, which is
explained later in this chapter.

For example, the Doctrine Mapping Type ``string`` defines the
mapping from a PHP string to a SQL VARCHAR (or VARCHAR2 etc.
depending on the RDBMS brand). Here is a quick overview of the
built-in mapping types:


-  ``string``: Type that maps a SQL VARCHAR to a PHP string.
-  ``integer``: Type that maps a SQL INT to a PHP integer.
-  ``smallint``: Type that maps a database SMALLINT to a PHP
   integer.
-  ``bigint``: Type that maps a database BIGINT to a PHP string.
-  ``boolean``: Type that maps a SQL boolean to a PHP boolean.
-  ``decimal``: Type that maps a SQL DECIMAL to a PHP string.
-  ``date``: Type that maps a SQL DATETIME to a PHP DateTime
   object.
-  ``time``: Type that maps a SQL TIME to a PHP DateTime object.
-  ``datetime``: Type that maps a SQL DATETIME/TIMESTAMP to a PHP
   DateTime object.
-  ``datetimetz``: Type that maps a SQL DATETIME/TIMESTAMP to a PHP
   DateTime object with timezone.
-  ``text``: Type that maps a SQL CLOB to a PHP string.
-  ``object``: Type that maps a SQL CLOB to a PHP object using
   ``serialize()`` and ``unserialize()``
-  ``array``: Type that maps a SQL CLOB to a PHP array using
   ``serialize()`` and ``unserialize()``
-  ``simple_array``: Type that maps a SQL CLOB to a PHP array using
   ``implode()`` and ``explode()``, with a comma as delimiter. *IMPORTANT*
   Only use this type if you are sure that your values cannot contain a ",".
-  ``json_array``: Type that maps a SQL CLOB to a PHP array using
   ``json_encode()`` and ``json_decode()``
-  ``float``: Type that maps a SQL Float (Double Precision) to a
   PHP double. *IMPORTANT*: Works only with locale settings that use
   decimal points as separator.
-  ``guid``: Type that maps a database GUID/UUID to a PHP string. Defaults to
   varchar but uses a specific type if the platform supports it.
-  ``blob``: Type that maps a SQL BLOB to a PHP resource stream

.. note::

    Doctrine Mapping Types are NOT SQL types and NOT PHP
    types! They are mapping types between 2 types.
    Additionally Mapping types are *case-sensitive*. For example, using
    a DateTime column will NOT match the datetime type that ships with
    Doctrine 2.

.. note::

    DateTime and Object types are compared by reference, not by value. Doctrine updates this values
    if the reference changes and therefore behaves as if these objects are immutable value objects.

.. warning::

    All Date types assume that you are exclusively using the default timezone
    set by `date_default_timezone_set() <http://docs.php.net/manual/en/function.date-default-timezone-set.php>`_
    or by the php.ini configuration ``date.timezone``. Working with
    different timezones will cause troubles and unexpected behavior.

    If you need specific timezone handling you have to handle this
    in your domain, converting all the values back and forth from UTC.
    There is also a :doc:`cookbook entry <../cookbook/working-with-datetime>`
    on working with datetimes that gives hints for implementing
    multi timezone applications.


Property Mapping
----------------

After a class has been marked as an entity it can specify mappings
for its instance fields. Here we will only look at simple fields
that hold scalar values like strings, numbers, etc. Associations to
other objects are covered in the chapter "Association Mapping".

To mark a property for relational persistence the ``@Column``
docblock annotation is used. This annotation usually requires at
least 1 attribute to be set, the ``type``. The ``type`` attribute
specifies the Doctrine Mapping Type to use for the field. If the
type is not specified, 'string' is used as the default mapping type
since it is the most flexible.

Example:

.. configuration-block::

    .. code-block:: php

        <?php
        /** @Entity */
        class MyPersistentClass
        {
            /** @Column(type="integer") */
            private $id;
            /** @Column(length=50) */
            private $name; // type defaults to string
            //...
        }

    .. code-block:: xml

        <doctrine-mapping>
          <entity name="MyPersistentClass">
            <field name="id" type="integer" />
            <field name="name" length="50" />
          </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
          type: entity
          fields:
            id:
              type: integer
            name:
              length: 50

In that example we mapped the field ``id`` to the column ``id``
using the mapping type ``integer`` and the field ``name`` is mapped
to the column ``name`` with the default mapping type ``string``. As
you can see, by default the column names are assumed to be the same
as the field names. To specify a different name for the column, you
can use the ``name`` attribute of the Column annotation as
follows:

.. configuration-block::

    .. code-block:: php

        <?php
        /** @Column(name="db_name") */
        private $name;

    .. code-block:: xml

        <doctrine-mapping>
          <entity name="MyPersistentClass">
            <field name="name" column="db_name" />
          </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
          type: entity
          fields:
            name:
              length: 50
              column: db_name

The Column annotation has some more attributes. Here is a complete
list:


-  ``type``: (optional, defaults to 'string') The mapping type to
   use for the column.
-  ``column``: (optional, defaults to field name) The name of the
   column in the database.
-  ``length``: (optional, default 255) The length of the column in
   the database. (Applies only if a string-valued column is used).
-  ``unique``: (optional, default FALSE) Whether the column is a
   unique key.
-  ``nullable``: (optional, default FALSE) Whether the database
   column is nullable.
-  ``precision``: (optional, default 0) The precision for a decimal
   (exact numeric) column. (Applies only if a decimal column is used.)
-  ``scale``: (optional, default 0) The scale for a decimal (exact
   numeric) column. (Applies only if a decimal column is used.)

.. _reference-basic-mapping-custom-mapping-types:

Custom Mapping Types
--------------------

Doctrine allows you to create new mapping types. This can come in
handy when you're missing a specific mapping type or when you want
to replace the existing implementation of a mapping type.

In order to create a new mapping type you need to subclass
``Doctrine\DBAL\Types\Type`` and implement/override the methods as
you wish. Here is an example skeleton of such a custom type class:

.. code-block:: php

    <?php
    namespace My\Project\Types;
    
    use Doctrine\DBAL\Types\Type;
    use Doctrine\DBAL\Platforms\AbstractPlatform;
    
    /**
     * My custom datatype.
     */
    class MyType extends Type
    {
        const MYTYPE = 'mytype'; // modify to match your type name
    
        public function getSqlDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
        {
            // return the SQL used to create your column type. To create a portable column type, use the $platform.
        }
    
        public function convertToPHPValue($value, AbstractPlatform $platform)
        {
            // This is executed when the value is read from the database. Make your conversions here, optionally using the $platform.
        }
    
        public function convertToDatabaseValue($value, AbstractPlatform $platform)
        {
            // This is executed when the value is written to the database. Make your conversions here, optionally using the $platform.
        }
    
        public function getName()
        {
            return self::MYTYPE; // modify to match your constant name
        }
    }

Restrictions to keep in mind:


-  If the value of the field is *NULL* the method
   ``convertToDatabaseValue()`` is not called.
-  The ``UnitOfWork`` never passes values to the database convert
   method that did not change in the request.

When you have implemented the type you still need to let Doctrine
know about it. This can be achieved through the
``Doctrine\DBAL\Types\Type#addType($name, $className)``
method. See the following example:

.. code-block:: php

    <?php
    // in bootstrapping code
    
    // ...
    
    use Doctrine\DBAL\Types\Type;
    
    // ...
    
    // Register my type
    Type::addType('mytype', 'My\Project\Types\MyType');

As can be seen above, when registering the custom types in the
configuration you specify a unique name for the mapping type and
map that to the corresponding fully qualified class name. Now you
can use your new type in your mapping like this:

.. code-block:: php

    <?php
    class MyPersistentClass
    {
        /** @Column(type="mytype") */
        private $field;
    }

To have Schema-Tool convert the underlying database type of your
new "mytype" directly into an instance of ``MyType`` you have to
additionally register this mapping with your database platform:

.. code-block:: php

    <?php
    $conn = $em->getConnection();
    $conn->getDatabasePlatform()->registerDoctrineTypeMapping('db_mytype', 'mytype');

Now using Schema-Tool, whenever it detects a column having the
``db_mytype`` it will convert it into a ``mytype`` Doctrine Type
instance for Schema representation. Keep in mind that you can
easily produce clashes this way, each database type can only map to
exactly one Doctrine mapping type.

Custom ColumnDefinition
-----------------------

You can define a custom definition for each column using the "columnDefinition"
attribute of ``@Column``. You have to define all the definitions that follow
the name of a column here.

.. note::

    Using columnDefinition will break change-detection in SchemaTool.

Identifiers / Primary Keys
--------------------------

Every entity class needs an identifier/primary key. You designate
the field that serves as the identifier with the ``@Id`` marker
annotation. Here is an example:

.. configuration-block::

    .. code-block:: php

        <?php
        class MyPersistentClass
        {
            /** @Id @Column(type="integer") */
            private $id;
            //...
        }

    .. code-block:: xml

        <doctrine-mapping>
          <entity name="MyPersistentClass">
            <id name="id" type="integer" />
            <field name="name" length="50" />
          </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
          type: entity
          id:
            id:
              type: integer
          fields:
            name:
              length: 50

Without doing anything else, the identifier is assumed to be
manually assigned. That means your code would need to properly set
the identifier property before passing a new entity to
``EntityManager#persist($entity)``.

A common alternative strategy is to use a generated value as the
identifier. To do this, you use the ``@GeneratedValue`` annotation
like this:

.. configuration-block::

    .. code-block:: php

        <?php
        class MyPersistentClass
        {
            /**
             * @Id @Column(type="integer")
             * @GeneratedValue
             */
            private $id;
        }

    .. code-block:: xml

        <doctrine-mapping>
          <entity name="MyPersistentClass">
            <id name="id" type="integer">
                <generator strategy="AUTO" />
            </id>
            <field name="name" length="50" />
          </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        MyPersistentClass:
          type: entity
          id:
            id:
              type: integer
              generator:
                strategy: AUTO
          fields:
            name:
              length: 50

This tells Doctrine to automatically generate a value for the
identifier. How this value is generated is specified by the
``strategy`` attribute, which is optional and defaults to 'AUTO'. A
value of ``AUTO`` tells Doctrine to use the generation strategy
that is preferred by the currently used database platform. See
below for details.

Identifier Generation Strategies
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The previous example showed how to use the default identifier
generation strategy without knowing the underlying database with
the AUTO-detection strategy. It is also possible to specify the
identifier generation strategy more explicitly, which allows to
make use of some additional features.

Here is the list of possible generation strategies:


-  ``AUTO`` (default): Tells Doctrine to pick the strategy that is
   preferred by the used database platform. The preferred strategies
   are IDENTITY for MySQL, SQLite and MsSQL and SEQUENCE for Oracle
   and PostgreSQL. This strategy provides full portability.
-  ``SEQUENCE``: Tells Doctrine to use a database sequence for ID
   generation. This strategy does currently not provide full
   portability. Sequences are supported by Oracle and PostgreSql.
-  ``IDENTITY``: Tells Doctrine to use special identity columns in
   the database that generate a value on insertion of a row. This
   strategy does currently not provide full portability and is
   supported by the following platforms: MySQL/SQLite
   (AUTO\_INCREMENT), MSSQL (IDENTITY) and PostgreSQL (SERIAL).
-  ``TABLE``: Tells Doctrine to use a separate table for ID
   generation. This strategy provides full portability.
   ***This strategy is not yet implemented!***
-  ``NONE``: Tells Doctrine that the identifiers are assigned (and
   thus generated) by your code. The assignment must take place before
   a new entity is passed to ``EntityManager#persist``. NONE is the
   same as leaving off the @GeneratedValue entirely.

Sequence Generator
^^^^^^^^^^^^^^^^^^

The Sequence Generator can currently be used in conjunction with
Oracle or Postgres and allows some additional configuration options
besides specifying the sequence's name:

.. configuration-block::

    .. code-block:: php

        <?php
        class User
        {
            /**
             * @Id
             * @GeneratedValue(strategy="SEQUENCE")
             * @SequenceGenerator(sequenceName="tablename_seq", initialValue=1, allocationSize=100)
             */
            protected $id = null;
        }

    .. code-block:: xml

        <doctrine-mapping>
          <entity name="User">
            <id name="id" type="integer">
                <generator strategy="SEQUENCE" />
                <sequence-generator sequence-name="tablename_seq" allocation-size="100" initial-value="1" />
            </id>
          </entity>
        </doctrine-mapping>
 
    .. code-block:: yaml

        MyPersistentClass:
          type: entity
          id:
            id:
              type: integer
              generator:
                strategy: SEQUENCE
              sequenceGenerator:
                sequenceName: tablename_seq
                allocationSize: 100
                initialValue: 1

The initial value specifies at which value the sequence should
start.

The allocationSize is a powerful feature to optimize INSERT
performance of Doctrine. The allocationSize specifies by how much
values the sequence is incremented whenever the next value is
retrieved. If this is larger than 1 (one) Doctrine can generate
identifier values for the allocationSizes amount of entities. In
the above example with ``allocationSize=100`` Doctrine 2 would only
need to access the sequence once to generate the identifiers for
100 new entities.

*The default allocationSize for a @SequenceGenerator is currently 10.*

.. caution::

    The allocationSize is detected by SchemaTool and
    transformed into an "INCREMENT BY " clause in the CREATE SEQUENCE
    statement. For a database schema created manually (and not
    SchemaTool) you have to make sure that the allocationSize
    configuration option is never larger than the actual sequences
    INCREMENT BY value, otherwise you may get duplicate keys.


.. note::

    It is possible to use strategy="AUTO" and at the same time
    specifying a @SequenceGenerator. In such a case, your custom
    sequence settings are used in the case where the preferred strategy
    of the underlying platform is SEQUENCE, such as for Oracle and
    PostgreSQL.


Composite Keys
~~~~~~~~~~~~~~

Doctrine 2 allows to use composite primary keys. There are however
some restrictions opposed to using a single identifier. The use of
the ``@GeneratedValue`` annotation is only supported for simple
(not composite) primary keys, which means you can only use
composite keys if you generate the primary key values yourself
before calling ``EntityManager#persist()`` on the entity.

To designate a composite primary key / identifier, simply put the
@Id marker annotation on all fields that make up the primary key.

Quoting Reserved Words
----------------------

It may sometimes be necessary to quote a column or table name
because it conflicts with a reserved word of the particular RDBMS
in use. This is often referred to as "Identifier Quoting". To let
Doctrine know that you would like a table or column name to be
quoted in all SQL statements, enclose the table or column name in
backticks. Here is an example:

.. code-block:: php

    <?php
    /** @Column(name="`number`", type="integer") */
    private $number;

Doctrine will then quote this column name in all SQL statements
according to the used database platform.

.. warning::

    Identifier Quoting is not supported for join column
    names or discriminator column names.

.. warning::

    Identifier Quoting is a feature that is mainly intended
    to support legacy database schemas. The use of reserved words and
    identifier quoting is generally discouraged. Identifier quoting
    should not be used to enable the use non-standard-characters such
    as a dash in a hypothetical column ``test-name``. Also Schema-Tool
    will likely have troubles when quoting is used for case-sensitivity
    reasons (in Oracle for example).



