Annotations Reference
=====================

In this chapter a reference of every Doctrine 2 Annotation is given
with short explanations on their context and usage.

Index
-----

-  :ref:`@Column <annref_column>`
-  :ref:`@ChangeTrackingPolicy <annref_changetrackingpolicy>`
-  :ref:`@DiscriminatorColumn <annref_discriminatorcolumn>`
-  :ref:`@DiscriminatorMap <annref_discriminatormap>`
-  :ref:`@Entity <annref_entity>`
-  :ref:`@GeneratedValue <annref_generatedvalue>`
-  :ref:`@HasLifecycleCallbacks <annref_haslifecyclecallbacks>`
-  :ref:`@Index <annref_index>`
-  :ref:`@Id <annref_id>`
-  :ref:`@InheritanceType <annref_inheritancetype>`
-  :ref:`@JoinColumn <annref_joincolumn>`
-  :ref:`@JoinTable <annref_jointable>`
-  :ref:`@ManyToOne <annref_manytoone>`
-  :ref:`@ManyToMany <annref_manytomany>`
-  :ref:`@MappedSuperclass <annref_mappedsuperclass>`
-  :ref:`@OneToOne <annref_onetoone>`
-  :ref:`@OneToMany <annref_onetomany>`
-  :ref:`@OrderBy <annref_orderby>`
-  :ref:`@PostLoad <annref_postload>`
-  :ref:`@PostPersist <annref_postpersist>`
-  :ref:`@PostRemove <annref_postremove>`
-  :ref:`@PostUpdate <annref_postupdate>`
-  :ref:`@PrePersist <annref_prepersist>`
-  :ref:`@PreRemove <annref_preremove>`
-  :ref:`@PreUpdate <annref_preupdate>`
-  :ref:`@SequenceGenerator <annref_sequencegenerator>`
-  :ref:`@Table <annref_table>`
-  :ref:`@UniqueConstraint <annref_uniqueconstraint>`
-  :ref:`@Version <annref_version>`

Reference
---------

.. _annref_column:

@Column
~~~~~~~

Marks an annotated instance variable as "persistent". It has to be
inside the instance variables PHP DocBlock comment. Any value hold
inside this variable will be saved to and loaded from the database
as part of the lifecycle of the instance variables entity-class.

Required attributes:

-  **type**: Name of the Doctrine Type which is converted between PHP
   and Database representation.

Optional attributes:

-  **name**: By default the property name is used for the database
   column name also, however the 'name' attribute allows you to
   determine the column name.

-  **length**: Used by the "string" type to determine its maximum
   length in the database. Doctrine does not validate the length of a
   string values for you.

-  **precision**: The precision for a decimal (exact numeric) column
   (Applies only for decimal column)

-  **scale**: The scale for a decimal (exact numeric) column (Applies
   only for decimal column)

-  **unique**: Boolean value to determine if the value of the column
   should be unique across all rows of the underlying entities table.

-  **nullable**: Determines if NULL values allowed for this column.

-  **columnDefinition**: DDL SQL snippet that starts after the column
   name and specifies the complete (non-portable!) column definition.
   This attribute allows to make use of advanced RMDBS features.
   However you should make careful use of this feature and the
   consequences. SchemaTool will not detect changes on the column correctly
   anymore if you use "columnDefinition".

   Additionally you should remember that the "type"
   attribute still handles the conversion between PHP and Database
   values. If you use this attribute on a column that is used for
   joins between tables you should also take a look at
   :ref:`@JoinColumn <annref_joincolumn>`. 

Examples:

.. code-block:: php

    <?php
    /**
     * @Column(type="string", length=32, unique=true, nullable=false)
     */
    protected $username;
    
    /**
     * @Column(type="string", columnDefinition="CHAR(2) NOT NULL")
     */
    protected $country;
    
    /**
     * @Column(type="decimal", precision=2, scale=1)
     */
    protected $height;

.. _annref_changetrackingpolicy:

@ChangeTrackingPolicy
~~~~~~~~~~~~~~~~~~~~~

The Change Tracking Policy annotation allows to specify how the
Doctrine 2 UnitOfWork should detect changes in properties of
entities during flush. By default each entity is checked according
to a deferred implicit strategy, which means upon flush UnitOfWork
compares all the properties of an entity to a previously stored
snapshot. This works out of the box, however you might want to
tweak the flush performance where using another change tracking
policy is an interesting option.

The :doc:`details on all the available change tracking policies <change-tracking-policies>`
can be found in the configuration section.

Example:

.. code-block:: php

    <?php
    /**
     * @Entity
     * @ChangeTrackingPolicy("DEFERRED_IMPLICIT")
     * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
     * @ChangeTrackingPolicy("NOTIFY")
     */
    class User {}

.. _annref_discriminatorcolumn:

@DiscrimnatorColumn
~~~~~~~~~~~~~~~~~~~~~

This annotation is a required annotation for the topmost/super
class of an inheritance hierarchy. It specifies the details of the
column which saves the name of the class, which the entity is
actually instantiated as.

Required attributes:


-  **name**: The column name of the discriminator. This name is also
   used during Array hydration as key to specify the class-name.

Optional attributes:


-  **type**: By default this is string.
-  **length**: By default this is 255.

.. _annref_discriminatormap:

@DiscriminatorMap
~~~~~~~~~~~~~~~~~~~~~

The discriminator map is a required annotation on the
top-most/super class in an inheritance hierarchy. It takes an array
as only argument which defines which class should be saved under
which name in the database. Keys are the database value and values
are the classes, either as fully- or as unqualified class names
depending if the classes are in the namespace or not.

.. code-block:: php

    <?php
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

.. _annref_entity:

@Entity
~~~~~~~

Required annotation to mark a PHP class as Entity. Doctrine manages
the persistence of all classes marked as entity.

Optional attributes:


-  **repositoryClass**: Specifies the FQCN of a subclass of the
   Doctrine. Use of repositories for entities is encouraged to keep
   specialized DQL and SQL operations separated from the Model/Domain
   Layer.
-  **readOnly**: (>= 2.1) Specifies that this entity is marked as read only and not
   considered for change-tracking. Entities of this type can be persisted
   and removed though.

Example:

.. code-block:: php

    <?php
    /**
     * @Entity(repositoryClass="MyProject\UserRepository")
     */
    class User
    {
        //...
    }

.. _annref_generatedvalue:

@GeneratedValue
~~~~~~~~~~~~~~~~~~~~~

Specifies which strategy is used for identifier generation for an
instance variable which is annotated by :ref:`@Id <annref_id>`. This
annotation is optional and only has meaning when used in
conjunction with @Id.

If this annotation is not specified with @Id the NONE strategy is
used as default.

Required attributes:


-  **strategy**: Set the name of the identifier generation strategy.
   Valid values are AUTO, SEQUENCE, TABLE, IDENTITY and NONE.

Example:

.. code-block:: php

    <?php
    /**
     * @Id
     * @Column(type="integer")
     * @generatedValue(strategy="IDENTITY")
     */
    protected $id = null;

.. _annref_haslifecyclecallbacks:

@HasLifecycleCallbacks
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Annotation which has to be set on the entity-class PHP DocBlock to
notify Doctrine that this entity has entity life-cycle callback
annotations set on at least one of its methods. Using @PostLoad,
@PrePersist, @PostPersist, @PreRemove, @PostRemove, @PreUpdate or
@PostUpdate without this marker annotation will make Doctrine
ignore the callbacks.

Example:

.. code-block:: php

    <?php
    /**
     * @Entity
     * @HasLifecycleCallbacks
     */
    class User
    {
        /**
         * @PostPersist
         */
        public function sendOptinMail() {}
    }

.. _annref_index:

@Index
~~~~~~~

Annotation is used inside the :ref:`@Table <annref_table>` annotation on
the entity-class level. It allows to hint the SchemaTool to
generate a database index on the specified table columns. It only
has meaning in the SchemaTool schema generation context.

Required attributes:


-  **name**: Name of the Index
-  **columns**: Array of columns.

Example:

.. code-block:: php

    <?php
    /**
     * @Entity
     * @Table(name="ecommerce_products",indexes={@index(name="search_idx", columns={"name", "email"})})
     */
    class ECommerceProduct
    {
    }

.. _annref_id:

@Id
~~~~~~~

The annotated instance variable will be marked as entity
identifier, the primary key in the database. This annotation is a
marker only and has no required or optional attributes. For
entities that have multiple identifier columns each column has to
be marked with @Id.

Example:

.. code-block:: php

    <?php
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id = null;

.. _annref_inheritancetype:

@InheritanceType
~~~~~~~~~~~~~~~~~~~~~

In an inheritance hierarchy you have to use this annotation on the
topmost/super class to define which strategy should be used for
inheritance. Currently Single Table and Class Table Inheritance are
supported.

This annotation has always been used in conjunction with the
:ref:`@DiscriminatorMap <annref_discriminatormap>` and
:ref:`@DiscriminatorColumn <annref_discriminatorcolumn>` annotations.

Examples:

.. code-block:: php

    <?php
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
     * @InheritanceType("JOINED")
     * @DiscriminatorColumn(name="discr", type="string")
     * @DiscriminatorMap({"person" = "Person", "employee" = "Employee"})
     */
    class Person
    {
        // ...
    }

.. _annref_joincolumn:

@JoinColumn
~~~~~~~~~~~~~~

This annotation is used in the context of relations in
:ref:`@ManyToOne <annref_manytoone>`, :ref:`@OneToOne <annref_onetoone>` fields
and in the Context of :ref:`@JoinTable <annref_jointable>` nested inside
a @ManyToMany. This annotation is not required. If its not
specified the attributes *name* and *referencedColumnName* are
inferred from the table and primary key names.

Required attributes:


-  **name**: Column name that holds the foreign key identifier for
   this relation. In the context of @JoinTable it specifies the column
   name in the join table.
-  **referencedColumnName**: Name of the primary key identifier that
   is used for joining of this relation.

Optional attributes:


-  **unique**: Determines if this relation exclusive between the
   affected entities and should be enforced so on the database
   constraint level. Defaults to false.
-  **nullable**: Determine if the related entity is required, or if
   null is an allowed state for the relation. Defaults to true.
-  **onDelete**: Cascade Action (Database-level)
-  **onUpdate**: Cascade Action (Database-level)
-  **columnDefinition**: DDL SQL snippet that starts after the column
   name and specifies the complete (non-portable!) column definition.
   This attribute allows to make use of advanced RMDBS features. Using
   this attribute on @JoinColumn is necessary if you need slightly
   different column definitions for joining columns, for example
   regarding NULL/NOT NULL defaults. However by default a
   "columnDefinition" attribute on :ref:`@Column <annref_column>` also sets
   the related @JoinColumn's columnDefinition. This is necessary to
   make foreign keys work.

Example:

.. code-block:: php

    <?php
    /**
     * @OneToOne(targetEntity="Customer")
     * @JoinColumn(name="customer_id", referencedColumnName="id")
     */
    private $customer;

.. _annref_joincolumns:

@JoinColumns
~~~~~~~~~~~~~~

An array of @JoinColumn annotations for a
:ref:`@ManyToOne <annref_manytoone>` or :ref:`@OneToOne <annref_onetoone>`
relation with an entity that has multiple identifiers.

.. _annref_jointable:

@JoinTable
~~~~~~~~~~~~~~

Using :ref:`@OneToMany <annref_onetomany>` or
:ref:`@ManyToMany <annref_manytomany>` on the owning side of the relation
requires to specify the @JoinTable annotation which describes the
details of the database join table. If you do not specify
@JoinTable on these relations reasonable mapping defaults apply
using the affected table and the column names.

Required attributes:


-  **name**: Database name of the join-table
-  **joinColumns**: An array of @JoinColumn annotations describing the
   join-relation between the owning entities table and the join table.
-  **inverseJoinColumns**: An array of @JoinColumn annotations
   describing the join-relation between the inverse entities table and
   the join table.

Example:

.. code-block:: php

    <?php
    /**
     * @ManyToMany(targetEntity="Phonenumber")
     * @JoinTable(name="users_phonenumbers",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="phonenumber_id", referencedColumnName="id", unique=true)}
     * )
     */
    public $phonenumbers;

.. _annref_manytoone:

@ManyToOne
~~~~~~~~~~~~~~

Defines that the annotated instance variable holds a reference that
describes a many-to-one relationship between two entities.

Required attributes:


-  **targetEntity**: FQCN of the referenced target entity. Can be the
   unqualified class name if both classes are in the same namespace.
   *IMPORTANT:* No leading backslash!

Optional attributes:


-  **cascade**: Cascade Option
-  **fetch**: One of LAZY or EAGER
-  inversedBy - The inversedBy attribute designates the field in
   the entity that is the inverse side of the relationship.

Example:

.. code-block:: php

    <?php
    /**
     * @ManyToOne(targetEntity="Cart", cascade={"all"}, fetch="EAGER")
     */
    private $cart;

.. _annref_manytomany:

@ManyToMany
~~~~~~~~~~~~~~

Defines an instance variable holds a many-to-many relationship
between two entities. :ref:`@JoinTable <annref_jointable>` is an
additional, optional annotation that has reasonable default
configuration values using the table and names of the two related
entities.

Required attributes:


-  **targetEntity**: FQCN of the referenced target entity. Can be the
   unqualified class name if both classes are in the same namespace.
   *IMPORTANT:* No leading backslash!

Optional attributes:


-  **mappedBy**: This option specifies the property name on the
   targetEntity that is the owning side of this relation. Its a
   required attribute for the inverse side of a relationship.
-  **inversedBy**: The inversedBy attribute designates the ﬁeld in the
   entity that is the inverse side of the relationship.
-  **cascade**: Cascade Option
-  **fetch**: One of LAZY or EAGER

    **NOTE** For ManyToMany bidirectional relationships either side may
    be the owning side (the side that defines the @JoinTable and/or
    does not make use of the mappedBy attribute, thus using a default
    join table).


Example:

.. code-block:: php

    <?php
    /**
     * Owning Side
     *
     * @ManyToMany(targetEntity="Group", inversedBy="features")
     * @JoinTable(name="user_groups",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
     *      )
     */
    private $groups;
    
    /**
     * Inverse Side
     *
     * @ManyToMany(targetEntity="User", mappedBy="groups")
     */
    private $features;

.. _annref_mappedsuperclass:

@MappedSuperclass
~~~~~~~~~~~~~~~~~~~~~

An mapped superclass is an abstract or concrete class that provides
persistent entity state and mapping information for its subclasses,
but which is not itself an entity. This annotation is specified on
the Class docblock and has no additional attributes.

The @MappedSuperclass annotation cannot be used in conjunction with
@Entity. See the Inheritance Mapping section for
:doc:`more details on the restrictions of mapped superclasses <inheritance-mapping>`.

.. _annref_onetoone:

@OneToOne
~~~~~~~~~~~~~~

The @OneToOne annotation works almost exactly as the
:ref:`@ManyToOne <annref_manytoone>` with one additional option that can
be specified. The configuration defaults for
:ref:`@JoinColumn <annref_joincolumn>` using the target entity table and
primary key column names apply here too.

Required attributes:


-  **targetEntity**: FQCN of the referenced target entity. Can be the
   unqualified class name if both classes are in the same namespace.
   *IMPORTANT:* No leading backslash!

Optional attributes:


-  **cascade**: Cascade Option
-  **fetch**: One of LAZY or EAGER
-  **orphanRemoval**: Boolean that specifies if orphans, inverse
   OneToOne entities that are not connected to any owning instance,
   should be removed by Doctrine. Defaults to false.
-  **inversedBy**: The inversedBy attribute designates the ﬁeld in the
   entity that is the inverse side of the relationship.

Example:

.. code-block:: php

    <?php
    /**
     * @OneToOne(targetEntity="Customer")
     * @JoinColumn(name="customer_id", referencedColumnName="id")
     */
    private $customer;

.. _annref_onetomany:

@OneToMany
~~~~~~~~~~~~~~

Required attributes:


-  **targetEntity**: FQCN of the referenced target entity. Can be the
   unqualified class name if both classes are in the same namespace.
   *IMPORTANT:* No leading backslash!

Optional attributes:


-  **cascade**: Cascade Option
-  **orphanRemoval**: Boolean that specifies if orphans, inverse
   OneToOne entities that are not connected to any owning instance,
   should be removed by Doctrine. Defaults to false.
-  **mappedBy**: This option specifies the property name on the
   targetEntity that is the owning side of this relation. Its a
   required attribute for the inverse side of a relationship.

Example:

.. code-block:: php

    <?php
    /**
     * @OneToMany(targetEntity="Phonenumber", mappedBy="user", cascade={"persist", "remove", "merge"}, orphanRemoval=true)
     */
    public $phonenumbers;

.. _annref_orderby:

@OrderBy
~~~~~~~~~~~~~~

Optional annotation that can be specified with a
:ref:`@ManyToMany <annref_manytomany>` or :ref:`@OneToMany <annref_onetomany>`
annotation to specify by which criteria the collection should be
retrieved from the database by using an ORDER BY clause.

This annotation requires a single non-attributed value with an DQL
snippet:

Example:

.. code-block:: php

    <?php
    /**
     * @ManyToMany(targetEntity="Group")
     * @OrderBy({"name" = "ASC"})
     */
    private $groups;

The DQL Snippet in OrderBy is only allowed to consist of
unqualified, unquoted field names and of an optional ASC/DESC
positional statement. Multiple Fields are separated by a comma (,).
The referenced field names have to exist on the ``targetEntity``
class of the ``@ManyToMany`` or ``@OneToMany`` annotation.

.. _annref_postload:

@PostLoad
~~~~~~~~~~~~~~

Marks a method on the entity to be called as a @PostLoad event.
Only works with @HasLifecycleCallbacks in the entity class PHP
DocBlock.

.. _annref_postpersist:

@PostPersist
~~~~~~~~~~~~~~

Marks a method on the entity to be called as a @PostPersist event.
Only works with @HasLifecycleCallbacks in the entity class PHP
DocBlock.

.. _annref_postremove:

@PostRemove
~~~~~~~~~~~~~~

Marks a method on the entity to be called as a @PostRemove event.
Only works with @HasLifecycleCallbacks in the entity class PHP
DocBlock.

.. _annref_postupdate:

@PostUpdate
~~~~~~~~~~~~~~

Marks a method on the entity to be called as a @PostUpdate event.
Only works with @HasLifecycleCallbacks in the entity class PHP
DocBlock.

.. _annref_prepersist:

@PrePersist
~~~~~~~~~~~~~~

Marks a method on the entity to be called as a @PrePersist event.
Only works with @HasLifecycleCallbacks in the entity class PHP
DocBlock.

.. _annref_preremove:

@PreRemove
~~~~~~~~~~~~~~

Marks a method on the entity to be called as a @PreRemove event.
Only works with @HasLifecycleCallbacks in the entity class PHP
DocBlock.

.. _annref_preupdate:

@PreUpdate
~~~~~~~~~~~~~~

Marks a method on the entity to be called as a @PreUpdate event.
Only works with @HasLifecycleCallbacks in the entity class PHP
DocBlock.

.. _annref_sequencegenerator:

@SequenceGenerator
~~~~~~~~~~~~~~~~~~~~~

For the use with @generatedValue(strategy="SEQUENCE") this
annotation allows to specify details about the sequence, such as
the increment size and initial values of the sequence.

Required attributes:


-  **sequenceName**: Name of the sequence

Optional attributes:


-  **allocationSize**: Increment the sequence by the allocation size
   when its fetched. A value larger than 1 allows to optimize for
   scenarios where you create more than one new entity per request.
   Defaults to 10
-  **initialValue**: Where does the sequence start, defaults to 1.

Example:

.. code-block:: php

    <?php
    /**
     * @Id
     * @GeneratedValue(strategy="SEQUENCE")
     * @Column(type="integer")
     * @SequenceGenerator(sequenceName="tablename_seq", initialValue=1, allocationSize=100)
     */
    protected $id = null;

.. _annref_table:

@Table
~~~~~~~

Annotation describes the table an entity is persisted in. It is
placed on the entity-class PHP DocBlock and is optional. If it is
not specified the table name will default to the entities
unqualified classname.

Required attributes:


-  **name**: Name of the table

Optional attributes:


-  **indexes**: Array of @Index annotations
-  **uniqueConstraints**: Array of @UniqueConstraint annotations.

Example:

.. code-block:: php

    <?php
    /**
     * @Entity
     * @Table(name="user",
     *      uniqueConstraints={@UniqueConstraint(name="user_unique",columns={"username"})},
     *      indexes={@Index(name="user_idx", columns={"email"})}
     * )
     */
    class User { }

.. _annref_uniqueconstraint:

@UniqueConstraint
~~~~~~~~~~~~~~~~~~~~~

Annotation is used inside the :ref:`@Table <annref_table>` annotation on
the entity-class level. It allows to hint the SchemaTool to
generate a database unique constraint on the specified table
columns. It only has meaning in the SchemaTool schema generation
context.

Required attributes:


-  **name**: Name of the Index
-  **columns**: Array of columns.

Example:

.. code-block:: php

    <?php
    /**
     * @Entity
     * @Table(name="ecommerce_products",uniqueConstraints={@UniqueConstraint(name="search_idx", columns={"name", "email"})})
     */
    class ECommerceProduct
    {
    }

.. _annref_version:

@Version
~~~~~~~~~~~~~~

Marker annotation that defines a specified column as version
attribute used in an optimistic locking scenario. It only works on
:ref:`@Column <annref_column>` annotations that have the type integer or
datetime. Combining @Version with :ref:`@Id <annref_id>` is not supported.

Example:

.. code-block:: php

    <?php
    /**
     * @column(type="integer")
     * @version
     */
    protected $version;

