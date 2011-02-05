Native SQL
==========

A ``NativeQuery`` lets you execute native SELECT SQL statements, mapping the results
according to your specifications. Such a specification that
describes how an SQL result set is mapped to a Doctrine result is
represented by a ``ResultSetMapping``. It describes how each column
of the database result should be mapped by Doctrine in terms of the
object graph. This allows you to map arbitrary SQL code to objects,
such as highly vendor-optimized SQL or stored-procedures.

.. note::

    If you want to execute DELETE, UPDATE or INSERT statements
    the Native SQL API cannot be used and will probably throw errors.
    Use ``EntityManager#getConnection()`` to access the native database
    connection and call the ``executeUpdate()`` method for these
    queries.

The NativeQuery class
---------------------

To create a ``NativeQuery`` you use the method
``EntityManager#createNativeQuery($sql, $resultSetMapping)``. As
you can see in the signature of this method, it expects 2
ingredients: The SQL you want to execute and the
``ResultSetMapping`` that describes how the results will be
mapped.

Once you obtained an instance of a ``NativeQuery``, you can bind
parameters to it and finally execute it.

The ResultSetMapping
--------------------

Understanding the ``ResultSetMapping`` is the key to using a
``NativeQuery``. A Doctrine result can contain the following
components:


-  Entity results. These represent root result elements.
-  Joined entity results. These represent joined entities in
   associations of root entity results.
-  Field results. These represent a column in the result set that
   maps to a field of an entity. A field result always belongs to an
   entity result or joined entity result.
-  Scalar results. These represent scalar values in the result set
   that will appear in each result row. Adding scalar results to a
   ResultSetMapping can also cause the overall result to become
   **mixed** (see DQL - Doctrine Query Language) if the same
   ResultSetMapping also contains entity results.
-  Meta results. These represent columns that contain
   meta-information, such as foreign keys and discriminator columns.
   When querying for objects (``getResult()``), all meta columns of
   root entities or joined entities must be present in the SQL query
   and mapped accordingly using ``ResultSetMapping#addMetaResult``.

.. note::

    It might not surprise you that Doctrine uses
    ``ResultSetMapping`` internally when you create DQL queries. As
    the query gets parsed and transformed to SQL, Doctrine fills a
    ``ResultSetMapping`` that describes how the results should be
    processed by the hydration routines.


We will now look at each of the result types that can appear in a
ResultSetMapping in detail.

Entity results
~~~~~~~~~~~~~~

An entity result describes an entity type that appears as a root
element in the transformed result. You add an entity result through
``ResultSetMapping#addEntityResult()``. Let's take a look at the
method signature in detail:

.. code-block:: php

    <?php
    /**
     * Adds an entity result to this ResultSetMapping.
     *
     * @param string $class The class name of the entity.
     * @param string $alias The alias for the class. The alias must be unique among all entity
     *                      results or joined entity results within this ResultSetMapping.
     */
    public function addEntityResult($class, $alias)

The first parameter is the fully qualified name of the entity
class. The second parameter is some arbitrary alias for this entity
result that must be unique within a ``ResultSetMapping``. You use
this alias to attach field results to the entity result. It is very
similar to an identification variable that you use in DQL to alias
classes or relationships.

An entity result alone is not enough to form a valid
``ResultSetMapping``. An entity result or joined entity result
always needs a set of field results, which we will look at soon.

Joined entity results
~~~~~~~~~~~~~~~~~~~~~

A joined entity result describes an entity type that appears as a
joined relationship element in the transformed result, attached to
a (root) entity result. You add a joined entity result through
``ResultSetMapping#addJoinedEntityResult()``. Let's take a look at
the method signature in detail:

.. code-block:: php

    <?php
    /**
     * Adds a joined entity result.
     *
     * @param string $class The class name of the joined entity.
     * @param string $alias The unique alias to use for the joined entity.
     * @param string $parentAlias The alias of the entity result that is the parent of this joined result.
     * @param object $relation The association field that connects the parent entity result with the joined entity result.
     */
    public function addJoinedEntityResult($class, $alias, $parentAlias, $relation)

The first parameter is the class name of the joined entity. The
second parameter is an arbitrary alias for the joined entity that
must be unique within the ``ResultSetMapping``. You use this alias
to attach field results to the entity result. The third parameter
is the alias of the entity result that is the parent type of the
joined relationship. The fourth and last parameter is the name of
the field on the parent entity result that should contain the
joined entity result.

Field results
~~~~~~~~~~~~~

A field result describes the mapping of a single column in an SQL
result set to a field in an entity. As such, field results are
inherently bound to entity results. You add a field result through
``ResultSetMapping#addFieldResult()``. Again, let's examine the
method signature in detail:

.. code-block:: php

    <?php
    /**
     * Adds a field result that is part of an entity result or joined entity result.
     *
     * @param string $alias The alias of the entity result or joined entity result.
     * @param string $columnName The name of the column in the SQL result set.
     * @param string $fieldName The name of the field on the (joined) entity.
     */
    public function addFieldResult($alias, $columnName, $fieldName)

The first parameter is the alias of the entity result to which the
field result will belong. The second parameter is the name of the
column in the SQL result set. Note that this name is case
sensitive, i.e. if you use a native query against Oracle it must be
all uppercase. The third parameter is the name of the field on the
entity result identified by ``$alias`` into which the value of the
column should be set.

Scalar results
~~~~~~~~~~~~~~

A scalar result describes the mapping of a single column in an SQL
result set to a scalar value in the Doctrine result. Scalar results
are typically used for aggregate values but any column in the SQL
result set can be mapped as a scalar value. To add a scalar result
use ``ResultSetMapping#addScalarResult()``. The method signature in
detail:

.. code-block:: php

    <?php
    /**
     * Adds a scalar result mapping.
     *
     * @param string $columnName The name of the column in the SQL result set.
     * @param string $alias The result alias with which the scalar result should be placed in the result structure.
     */
    public function addScalarResult($columnName, $alias)

The first parameter is the name of the column in the SQL result set
and the second parameter is the result alias under which the value
of the column will be placed in the transformed Doctrine result.

Meta results
~~~~~~~~~~~~

A meta result describes a single column in an SQL result set that
is either a foreign key or a discriminator column. These columns
are essential for Doctrine to properly construct objects out of SQL
result sets. To add a column as a meta result use
``ResultSetMapping#addMetaResult()``. The method signature in
detail:

.. code-block:: php

    <?php
    /**
     * Adds a meta column (foreign key or discriminator column) to the result set.
     * 
     * @param string $alias
     * @param string $columnAlias
     * @param string $columnName
     */
    public function addMetaResult($alias, $columnAlias, $columnName)

The first parameter is the alias of the entity result to which the
meta column belongs. A meta result column (foreign key or
discriminator column) always belongs to to an entity result. The
second parameter is the column alias/name of the column in the SQL
result set and the third parameter is the column name used in the
mapping.

Discriminator Column
~~~~~~~~~~~~~~~~~~~~

When joining an inheritance tree you have to give Doctrine a hint
which meta-column is the discriminator column of this tree.

.. code-block:: php

    <?php
    /**
     * Sets a discriminator column for an entity result or joined entity result.
     * The discriminator column will be used to determine the concrete class name to
     * instantiate.
     *
     * @param string $alias The alias of the entity result or joined entity result the discriminator
     *                      column should be used for.
     * @param string $discrColumn The name of the discriminator column in the SQL result set.
     * @todo Rename: addDiscriminatorColumn
     */
    public function setDiscriminatorColumn($alias, $discrColumn)

Examples
~~~~~~~~

Understanding a ResultSetMapping is probably easiest through
looking at some examples.

First a basic example that describes the mapping of a single
entity.

.. code-block:: php

    <?php
    // Equivalent DQL query: "select u from User u where u.name=?1"
    // User owns no associations.
    $rsm = new ResultSetMapping;
    $rsm->addEntityResult('User', 'u');
    $rsm->addFieldResult('u', 'id', 'id');
    $rsm->addFieldResult('u', 'name', 'name');
    
    $query = $this->_em->createNativeQuery('SELECT id, name FROM users WHERE name = ?', $rsm);
    $query->setParameter(1, 'romanb');
    
    $users = $query->getResult();

The result would look like this:

.. code-block:: php

    array(
        [0] => User (Object)
    )

Note that this would be a partial object if the entity has more
fields than just id and name. In the example above the column and
field names are identical but that is not necessary, of course.
Also note that the query string passed to createNativeQuery is
**real native SQL**. Doctrine does not touch this SQL in any way.

In the previous basic example, a User had no relations and the
table the class is mapped to owns no foreign keys. The next example
assumes User has a unidirectional or bidirectional one-to-one
association to a CmsAddress, where the User is the owning side and
thus owns the foreign key.

.. code-block:: php

    <?php
    // Equivalent DQL query: "select u from User u where u.name=?1"
    // User owns an association to an Address but the Address is not loaded in the query.
    $rsm = new ResultSetMapping;
    $rsm->addEntityResult('User', 'u');
    $rsm->addFieldResult('u', 'id', 'id');
    $rsm->addFieldResult('u', 'name', 'name');
    $rsm->addMetaResult('u', 'address_id', 'address_id');
    
    $query = $this->_em->createNativeQuery('SELECT id, name, address_id FROM users WHERE name = ?', $rsm);
    $query->setParameter(1, 'romanb');
    
    $users = $query->getResult();

Foreign keys are used by Doctrine for lazy-loading purposes when
querying for objects. In the previous example, each user object in
the result will have a proxy (a "ghost") in place of the address
that contains the address\_id. When the ghost proxy is accessed, it
loads itself based on this key.

Consequently, associations that are *fetch-joined* do not require
the foreign keys to be present in the SQL result set, only
associations that are lazy.

.. code-block:: php

    <?php
    // Equivalent DQL query: "select u from User u join u.address a WHERE u.name = ?1"
    // User owns association to an Address and the Address is loaded in the query.
    $rsm = new ResultSetMapping;
    $rsm->addEntityResult('User', 'u');
    $rsm->addFieldResult('u', 'id', 'id');
    $rsm->addFieldResult('u', 'name', 'name');
    $rsm->addJoinedEntityResult('Address' , 'a', 'u', 'address');
    $rsm->addFieldResult('a', 'address_id', 'id');
    $rsm->addFieldResult('a', 'street', 'street');
    $rsm->addFieldResult('a', 'city', 'city');
    
    $sql = 'SELECT u.id, u.name, a.id AS address_id, a.street, a.city FROM users u ' .
           'INNER JOIN address a ON u.address_id = a.id WHERE u.name = ?';
    $query = $this->_em->createNativeQuery($sql, $rsm);
    $query->setParameter(1, 'romanb');
    
    $users = $query->getResult();

In this case the nested entity ``Address`` is registered with the
``ResultSetMapping#addJoinedEntityResult`` method, which notifies
Doctrine that this entity is not hydrated at the root level, but as
a joined entity somewhere inside the object graph. In this case we
specify the alias 'u' as third parameter and ``address`` as fourth
parameter, which means the ``Address`` is hydrated into the
``User::$address`` property.

If a fetched entity is part of a mapped hierarchy that requires a
discriminator column, this column must be present in the result set
as a meta column so that Doctrine can create the appropriate
concrete type. This is shown in the following example where we
assume that there are one or more subclasses that extend User and
either Class Table Inheritance or Single Table Inheritance is used
to map the hierarchy (both use a discriminator column).

.. code-block:: php

    <?php
    // Equivalent DQL query: "select u from User u where u.name=?1"
    // User is a mapped base class for other classes. User owns no associations.
    $rsm = new ResultSetMapping;
    $rsm->addEntityResult('User', 'u');
    $rsm->addFieldResult('u', 'id', 'id');
    $rsm->addFieldResult('u', 'name', 'name');
    $rsm->addMetaResult('u', 'discr', 'discr'); // discriminator column
    $rsm->setDiscriminatorColumn('u', 'discr');
    
    $query = $this->_em->createNativeQuery('SELECT id, name, discr FROM users WHERE name = ?', $rsm);
    $query->setParameter(1, 'romanb');
    
    $users = $query->getResult();

Note that in the case of Class Table Inheritance, an example as
above would result in partial objects if any objects in the result
are actually a subtype of User. When using DQL, Doctrine
automatically includes the necessary joins for this mapping
strategy but with native SQL it is your responsibility.


