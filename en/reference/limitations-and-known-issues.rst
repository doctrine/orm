Limitations and Known Issues
============================

We try to make using Doctrine2 a very pleasant experience.
Therefore we think it is very important to be honest about the
current limitations to our users. Much like every other piece of
software Doctrine2 is not perfect and far from feature complete.
This section should give you an overview of current limitations of
Doctrine 2 as well as critical known issues that you should know
about.

Current Limitations
-------------------

There is a set of limitations that exist currently which might be
solved in the future. Any of this limitations now stated has at
least one ticket in the Tracker and is discussed for future
releases.

Foreign Keys as Identifiers
~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. note::

    Foreign keys as identifiers are currently in master and will be included in Doctrine 2.1

There are many use-cases where you would want to use an
Entity-Attribute-Value approach to modelling and define a
table-schema like the following:

.. code-block:: sql

    CREATE TABLE product (
        id INTEGER,
        name VARCHAR,
        PRIMARY KEY(id)
    );
    
    CREATE TABLE product_attributes (
        product_id INTEGER,
        attribute_name VARCHAR,
        attribute_value VARCHAR,
        PRIMARY KEY (product_id, attribute_name)
    );

This is currently *NOT* possible with Doctrine2. You have to define
a surrogate key on the ``product_attributes`` table and use a
unique-constraint for the ``product_id`` and ``attribute_name``.

.. code-block:: sql

    CREATE TABLE product_attributes (
        attribute_id, INTEGER,
        product_id INTEGER,
        attribute_name VARCHAR,
        attribute_value VARCHAR,
        PRIMARY KEY (attribute_id),
        UNIQUE (product_id, attribute_name)
    );

Although we state that we support composite primary keys that does
not currently include foreign keys as primary key columns. To see
the fundamental difference between the two different
``product_attributes`` tables you should see how they translate
into a Doctrine Mapping (Using Annotations):

.. code-block:: php

    <?php
    /**
     * Scenario 1: THIS IS NOT POSSIBLE CURRENTLY
     * @Entity @Table(name="product_attributes")
     */
    class ProductAttribute
    {
        /** @Id @ManyToOne(targetEntity="Product") */
        private $product;
    
        /** @Id @Column(type="string", name="attribute_name") */
        private $name;
    
        /** @Column(type="string", name="attribute_value") */
        private $value;
    }
    
    /**
     * Scenario 2: Using the surrogate key workaround
     * @Entity
     * @Table(name="product_attributes", uniqueConstraints={@UniqueConstraint(columns={"product_id", "attribute_name"})}))
     */
    class ProductAttribute
    {
        /** @Id @Column(type="integer") @GeneratedValue */
        private $id;
    
        /** @ManyToOne(targetEntity="Product") */
        private $product;
    
        /** @Column(type="string", name="attribute_name") */
        private $name;
    
        /** @Column(type="string", name="attribute_value") */
        private $value;
    }

The following Jira Issue
`contains the feature request to allow @ManyToOne and @OneToOne annotations along the @Id annotation <http://www.doctrine-project.org/jira/browse/DDC-117>`_.

Mapping Arrays to a Join Table
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Related to the previous limitation with "Foreign Keys as
Identifier" you might be interested in mapping the same table
structure as given above to an array. However this is not yet
possible either. See the following example:

.. code-block:: sql

    CREATE TABLE product (
        id INTEGER,
        name VARCHAR,
        PRIMARY KEY(id)
    );
    
    CREATE TABLE product_attributes (
        product_id INTEGER,
        attribute_name VARCHAR,
        attribute_value VARCHAR,
        PRIMARY KEY (product_id, attribute_name)
    );

This schema should be mapped to a Product Entity as follows:

.. code-block:: php

    class Product
    {
        private $id;
        private $name;
        private $attributes = array();
    }

Where the ``attribute_name`` column contains the key and
``attribute_value`` contains the value of each array element in
``$attributes``.

The feature request for persistence of primitive value arrays
`is described in the DDC-298 ticket <http://www.doctrine-project.org/jira/browse/DDC-298>`_.

Value Objects
~~~~~~~~~~~~~

There is currently no native support value objects in Doctrine
other than for ``DateTime`` instances or if you serialize the
objects using ``serialize()/deserialize()`` which the DBAL Type
"object" supports.

The feature request for full value-object support
`is described in the DDC-93 ticket <http://www.doctrine-project.org/jira/browse/DDC-93>`_.

Applying Filter Rules to any Query
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

There are scenarios in many applications where you want to apply
additional filter rules to each query implicitly. Examples
include:


-  In I18N Applications restrict results to a entities annotated
   with a specific locale
-  For a large collection always only return objects in a specific
   date range/where condition applied.
-  Soft-Delete

There is currently no way to achieve this consistently across both
DQL and Repository/Persister generated queries, but as this is a
pretty important feature we plan to add support for it in the
future.

Restricing Associations
~~~~~~~~~~~~~~~~~~~~~~~

There is currently no way to restrict associations to a subset of entities matching a given condition.
You should use a DQL query to retrieve a subset of associated entities. For example
if you need all visible articles in a certain category you could have the following code
in an entity repository:

.. code-block:: php

    <?php
    class ArticleRepository extends EntityRepository
    {
        public function getVisibleByCategory(Category $category)
        {
            $dql = "SELECT a FROM Article a WHERE a.category = ?1 and a.visible = true";
            return $this->getEntityManager()
                        ->createQuery($dql)
                        ->setParameter(1, $category)
                        ->getResult();
        }
    }

Cascade Merge with Bi-directional Associations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

There are two bugs now that concern the use of cascade merge in combination with bi-directional associations.
Make sure to study the behavior of cascade merge if you are using it:

-  `DDC-875 <http://www.doctrine-project.org/jira/browse/DDC-875>`_ Merge can sometimes add the same entity twice into a collection
-  `DDC-763 <http://www.doctrine-project.org/jira/browse/DDC-763>`_ Cascade merge on associated entities can insert too many rows through "Persistence by Reachability"

Custom Persisters
~~~~~~~~~~~~~~~~~

A Persister in Doctrine is an object that is responsible for the
hydration and write operations of an entity against the database.
Currently there is no way to overwrite the persister implementation
for a given entity, however there are several use-cases that can
benefit from custom persister implementations:


-  `Add Upsert Support <http://www.doctrine-project.org/jira/browse/DDC-668>`_
-  `Evaluate possible ways in which stored-procedures can be used <http://www.doctrine-project.org/jira/browse/DDC-445>`_
-  The previous Filter Rules Feature Request

Paginating Associations
~~~~~~~~~~~~~~~~~~~~~~~

.. note::

    Extra Lazy Collections are currently in master and will be included in Doctrine 2.1

It is not possible to paginate only parts of an associations at the moment. You can always only
load the whole association/collection into memory. This is rather problematic for large collections,
but we already plan to add facilities to fix this for Doctrine 2.1

-  `DDC-546 New Fetch Mode EXTRA_LAZY <http://www.doctrine-project.org/jira/browse/DDC-546>`_
-  `Blog: Working with large collections (Workaround) <http://www.doctrine-project.org/blog/doctrine2-large-collections>`_
-  `LargeCollections Helper <http://github.com/beberlei/DoctrineExtensions>`_

Persist Keys of Collections
~~~~~~~~~~~~~~~~~~~~~~~~~~~

PHP Arrays are ordered hash-maps and so should be the
``Doctrine\Common\Collections\Collection`` interface. We plan to
evaluate a feature that optionally persists and hydrates the keys
of a Collection instance.

`Ticket DDC-213 <http://www.doctrine-project.org/jira/browse/DDC-213>`_

Mapping many tables to one entity
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

It is not possible to map several equally looking tables onto one
entity. For example if you have a production and an archive table
of a certain business concept then you cannot have both tables map
to the same entity.

Behaviors
~~~~~~~~~

Doctrine 2 *will never* include a behavior system like Doctrine 1
in the core library. We don't think behaviors add more value than
they cost pain and debugging hell. Please see the many different
blog posts we have written on this topics:

-  `Doctrine2 "Behaviors" in a Nutshell <http://www.doctrine-project.org/blog/doctrine2-behaviours-nutshell>`_
-  `A re-usable Versionable behavior for Doctrine2 <http://www.doctrine-project.org/blog/doctrine2-versionable>`_
-  `Write your own ORM on top of Doctrine2 <http://www.doctrine-project.org/blog/your-own-orm-doctrine2>`_
-  `Doctrine 2 Behavioral Extensions <http://www.doctrine-project.org/blog/doctrine2-behavioral-extensions>`_
-  `Doctrator <https://github.com/pablodip/doctrator`>_

Doctrine 2 has enough hooks and extension points so that *you* can
add whatever you want on top of it. None of this will ever become
core functionality of Doctrine2 however, you will have to rely on
third party extensions for magical behaviors.

Nested Set
~~~~~~~~~~

NestedSet was offered as a behavior in Doctrine 1 and will not be
included in the core of Doctrine 2. However there are already two
extensions out there that offer support for Nested Set with
Doctrine 2:


-  `Doctrine2 Hierachical-Structural Behavior <http://github.com/guilhermeblanco/Doctrine2-Hierarchical-Structural-Behavior>`_
-  `Doctrine2 NestedSet <http://github.com/blt04/doctrine2-nestedset>`_

Known Issues
------------

The Known Issues section describes critical/blocker bugs and other
issues that are either complicated to fix, not fixable due to
backwards compatibility issues or where no simple fix exists (yet).
We don't plan to add every bug in the tracker there, just those
issues that can potentially cause nightmares or pain of any sort.

Identifier Quoting and Legacy Databases
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

For compatibility reasons between all the supported vendors and
edge case problems Doctrine 2 does *NOT* do automatic identifier
quoting. This can lead to problems when trying to get
legacy-databases to work with Doctrine 2.


-  You can quote column-names as described in the
   :doc:`Basic-Mapping <basic-mapping>` section.
-  You cannot quote join column names.
-  You cannot use non [a-zA-Z0-9\_]+ characters, they will break
   several SQL statements.

Having problems with these kind of column names? Many databases
support all CRUD operations on views that semantically map to
certain tables. You can create views for all your problematic
tables and column names to avoid the legacy quoting nightmare.

