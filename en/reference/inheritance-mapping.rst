Inheritance Mapping
===================

Mapped Superclasses
-------------------

An mapped superclass is an abstract or concrete class that provides
persistent entity state and mapping information for its subclasses,
but which is not itself an entity. Typically, the purpose of such a
mapped superclass is to define state and mapping information that
is common to multiple entity classes.

Mapped superclasses, just as regular, non-mapped classes, can
appear in the middle of an otherwise mapped inheritance hierarchy
(through Single Table Inheritance or Class Table Inheritance).

.. note::

    A mapped superclass cannot be an entity, it is not query-able and
    persistent relationships defined by a mapped superclass must be
    unidirectional (with an owning side only). This means that One-To-Many
    assocations are not possible on a mapped superclass at all.
    Furthermore Many-To-Many associations are only possible if the
    mapped superclass is only used in exactly one entity at the moment.
    For further support of inheritance, the single or
    joined table inheritance features have to be used.


Example:

.. code-block:: php

    <?php
    /** @MappedSuperclass */
    class MappedSuperclassBase
    {
        /** @Column(type="integer") */
        private $mapped1;
        /** @Column(type="string") */
        private $mapped2;
        /**
         * @OneToOne(targetEntity="MappedSuperclassRelated1")
         * @JoinColumn(name="related1_id", referencedColumnName="id")
         */
        private $mappedRelated1;
    
        // ... more fields and methods
    }
    
    /** @Entity */
    class EntitySubClass extends MappedSuperclassBase
    {
        /** @Id @Column(type="integer") */
        private $id;
        /** @Column(type="string") */
        private $name;
    
        // ... more fields and methods
    }

The DDL for the corresponding database schema would look something
like this (this is for SQLite):

.. code-block:: sql

    CREATE TABLE EntitySubClass (mapped1 INTEGER NOT NULL, mapped2 TEXT NOT NULL, id INTEGER NOT NULL, name TEXT NOT NULL, related1_id INTEGER DEFAULT NULL, PRIMARY KEY(id))

As you can see from this DDL snippet, there is only a single table
for the entity subclass. All the mappings from the mapped
superclass were inherited to the subclass as if they had been
defined on that class directly.

Single Table Inheritance
------------------------

`Single Table Inheritance <http://martinfowler.com/eaaCatalog/singleTableInheritance.html>`_
is an inheritance mapping strategy where all classes of a hierarchy
are mapped to a single database table. In order to distinguish
which row represents which type in the hierarchy a so-called
discriminator column is used.

Example:

.. code-block:: php

    <?php
    namespace MyProject\Model;
    
    /**
     * @Entity
     * @InheritanceType("SINGLE_TABLE")
     * @DiscriminatorColumn(name="discr", type="string")
     * @DiscriminatorMap({"person" = "Person", "employee" = "Employee"})
     */
    class Person
    {
        // ...
    }
    
    /**
     * @Entity
     */
    class Employee extends Person
    {
        // ...
    }

Things to note:


-  The @InheritanceType, @DiscriminatorColumn and @DiscriminatorMap
   must be specified on the topmost class that is part of the mapped
   entity hierarchy.
-  The @DiscriminatorMap specifies which values of the
   discriminator column identify a row as being of a certain type. In
   the case above a value of "person" identifies a row as being of
   type ``Person`` and "employee" identifies a row as being of type
   ``Employee``.
-  The names of the classes in the discriminator map do not need to
   be fully qualified if the classes are contained in the same
   namespace as the entity class on which the discriminator map is
   applied.

Design-time considerations
~~~~~~~~~~~~~~~~~~~~~~~~~~

This mapping approach works well when the type hierarchy is fairly
simple and stable. Adding a new type to the hierarchy and adding
fields to existing supertypes simply involves adding new columns to
the table, though in large deployments this may have an adverse
impact on the index and column layout inside the database.

Performance impact
~~~~~~~~~~~~~~~~~~

This strategy is very efficient for querying across all types in
the hierarchy or for specific types. No table joins are required,
only a WHERE clause listing the type identifiers. In particular,
relationships involving types that employ this mapping strategy are
very performant.

There is a general performance consideration with Single Table
Inheritance: If you use a STI entity as a many-to-one or one-to-one
entity you should never use one of the classes at the upper levels
of the inheritance hierachy as "targetEntity", only those that have
no subclasses. Otherwise Doctrine *CANNOT* create proxy instances
of this entity and will *ALWAYS* load the entity eagerly.

SQL Schema considerations
~~~~~~~~~~~~~~~~~~~~~~~~~

For Single-Table-Inheritance to work in scenarios where you are
using either a legacy database schema or a self-written database
schema you have to make sure that all columns that are not in the
root entity but in any of the different sub-entities has to allows
null values. Columns that have NOT NULL constraints have to be on
the root entity of the single-table inheritance hierarchy.

Class Table Inheritance
-----------------------

`Class Table Inheritance <http://martinfowler.com/eaaCatalog/classTableInheritance.html>`_
is an inheritance mapping strategy where each class in a hierarchy
is mapped to several tables: its own table and the tables of all
parent classes. The table of a child class is linked to the table
of a parent class through a foreign key constraint. Doctrine 2
implements this strategy through the use of a discriminator column
in the topmost table of the hierarchy because this is the easiest
way to achieve polymorphic queries with Class Table Inheritance.

Example:

.. code-block:: php

    <?php
    namespace MyProject\Model;
    
    /**
     * @Entity
     * @InheritanceType("JOINED")
     * @DiscriminatorColumn(name="discr", type="string")
     * @DiscriminatorMap({"person" = "Person", "employee" = "Employee"})
     */
    class Person
    {
        // ...
    }
    
    /** @Entity */
    class Employee extends Person
    {
        // ...
    }

Things to note:


-  The @InheritanceType, @DiscriminatorColumn and @DiscriminatorMap
   must be specified on the topmost class that is part of the mapped
   entity hierarchy.
-  The @DiscriminatorMap specifies which values of the
   discriminator column identify a row as being of which type. In the
   case above a value of "person" identifies a row as being of type
   ``Person`` and "employee" identifies a row as being of type
   ``Employee``.
-  The names of the classes in the discriminator map do not need to
   be fully qualified if the classes are contained in the same
   namespace as the entity class on which the discriminator map is
   applied.

.. note::

    When you do not use the SchemaTool to generate the
    required SQL you should know that deleting a class table
    inheritance makes use of the foreign key property
    ``ON DELETE CASCADE`` in all database implementations. A failure to
    implement this yourself will lead to dead rows in the database.


Design-time considerations
~~~~~~~~~~~~~~~~~~~~~~~~~~

Introducing a new type to the hierarchy, at any level, simply
involves interjecting a new table into the schema. Subtypes of that
type will automatically join with that new type at runtime.
Similarly, modifying any entity type in the hierarchy by adding,
modifying or removing fields affects only the immediate table
mapped to that type. This mapping strategy provides the greatest
flexibility at design time, since changes to any type are always
limited to that type's dedicated table.

Performance impact
~~~~~~~~~~~~~~~~~~

This strategy inherently requires multiple JOIN operations to
perform just about any query which can have a negative impact on
performance, especially with large tables and/or large hierarchies.
When partial objects are allowed, either globally or on the
specific query, then querying for any type will not cause the
tables of subtypes to be OUTER JOINed which can increase
performance but the resulting partial objects will not fully load
themselves on access of any subtype fields, so accessing fields of
subtypes after such a query is not safe.

There is a general performance consideration with Class Table
Inheritance: If you use a CTI entity as a many-to-one or one-to-one
entity you should never use one of the classes at the upper levels
of the inheritance hierachy as "targetEntity", only those that have
no subclasses. Otherwise Doctrine *CANNOT* create proxy instances
of this entity and will *ALWAYS* load the entity eagerly.

SQL Schema considerations
~~~~~~~~~~~~~~~~~~~~~~~~~

For each entity in the Class-Table Inheritance hierarchy all the
mapped fields have to be columns on the table of this entity.
Additionally each child table has to have an id column that matches
the id column definition on the root table (except for any sequence
or auto-increment details). Furthermore each child table has to
have a foreign key pointing from the id column to the root table id
column and cascading on delete.


