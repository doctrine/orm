Association Mapping
===================

This chapter explains how associations between entities are mapped
with Doctrine. We start out with an explanation of the concept of
owning and inverse sides which is important to understand when
working with bidirectional associations. Please read these
explanations carefully.

.. _association-mapping-owning-inverse:

Owning Side and Inverse Side
----------------------------

When mapping bidirectional associations it is important to
understand the concept of the owning and inverse sides. The
following general rules apply:


-  Relationships may be bidirectional or unidirectional.
-  A bidirectional relationship has both an owning side and an
   inverse side.
-  A unidirectional relationship only has an owning side.
-  The owning side of a relationship determines the updates to the
   relationship in the database.

The following rules apply to *bidirectional* associations:


-  The inverse side of a bidirectional relationship must refer to
   its owning side by use of the mappedBy attribute of the OneToOne,
   OneToMany, or ManyToMany mapping declaration. The mappedBy
   attribute designates the field in the entity that is the owner of
   the relationship.
-  The owning side of a bidirectional relationship must refer to
   its inverse side by use of the inversedBy attribute of the
   OneToOne, ManyToOne, or ManyToMany mapping declaration. The
   inversedBy attribute designates the field in the entity that is the
   inverse side of the relationship.
-  The many side of OneToMany/ManyToOne bidirectional relationships
   *must* be the owning side, hence the mappedBy element can not be
   specified on the ManyToOne side.
-  For OneToOne bidirectional relationships, the owning side
   corresponds to the side that contains the corresponding foreign key
   (@JoinColumn(s)).
-  For ManyToMany bidirectional relationships either side may be
   the owning side (the side that defines the @JoinTable and/or does
   not make use of the mappedBy attribute, thus using a default join
   table).

Especially important is the following:

**The owning side of a relationship determines the updates to the relationship in the database**.

To fully understand this, remember how bidirectional associations
are maintained in the object world. There are 2 references on each
side of the association and these 2 references both represent the
same association but can change independently of one another. Of
course, in a correct application the semantics of the bidirectional
association are properly maintained by the application developer
(that's his responsibility). Doctrine needs to know which of these
two in-memory references is the one that should be persisted and
which not. This is what the owning/inverse concept is mainly used
for.

**Changes made only to the inverse side of an association are ignored. Make sure to update both sides of a bidirectional association (or at least the owning side, from Doctrine's point of view)**

The owning side of a bidirectional association is the side Doctrine
"looks at" when determining the state of the association, and
consequently whether there is anything to do to update the
association in the database.

.. note::

    "Owning side" and "inverse side" are technical concepts of
    the ORM technology, not concepts of your domain model. What you
    consider as the owning side in your domain model can be different
    from what the owning side is for Doctrine. These are unrelated.


Collections
-----------

In all the examples of many-valued associations in this manual we
will make use of a ``Collection`` interface and a corresponding
default implementation ``ArrayCollection`` that are defined in the
``Doctrine\Common\Collections`` namespace. Why do we need that?
Doesn't that couple my domain model to Doctrine? Unfortunately, PHP
arrays, while being great for many things, do not make up for good
collections of business objects, especially not in the context of
an ORM. The reason is that plain PHP arrays can not be
transparently extended / instrumented in PHP code, which is
necessary for a lot of advanced ORM features. The classes /
interfaces that come closest to an OO collection are ArrayAccess
and ArrayObject but until instances of these types can be used in
all places where a plain array can be used (something that may
happen in PHP6) their usability is fairly limited. You "can"
type-hint on ``ArrayAccess`` instead of ``Collection``, since the
Collection interface extends ``ArrayAccess``, but this will
severely limit you in the way you can work with the collection,
because the ``ArrayAccess`` API is (intentionally) very primitive
and more importantly because you can not pass this collection to
all the useful PHP array functions, which makes it very hard to
work with.

.. warning::

    The Collection interface and ArrayCollection class,
    like everything else in the Doctrine namespace, are neither part of
    the ORM, nor the DBAL, it is a plain PHP class that has no outside
    dependencies apart from dependencies on PHP itself (and the SPL).
    Therefore using this class in your domain classes and elsewhere
    does not introduce a coupling to the persistence layer. The
    Collection class, like everything else in the Common namespace, is
    not part of the persistence layer. You could even copy that class
    over to your project if you want to remove Doctrine from your
    project and all your domain classes will work the same as before.


Mapping Defaults
----------------

Before we introduce all the association mappings in detail, you
should note that the @JoinColumn and @JoinTable definitions are
usually optional and have sensible default values. The defaults for
a join column in a one-to-one/many-to-one association is as
follows:

::

    name: "<fieldname>_id"
    referencedColumnName: "id"

As an example, consider this mapping:

.. code-block:: php

    <?php
    /** @OneToOne(targetEntity="Shipping") */
    private $shipping;

This is essentially the same as the following, more verbose,
mapping:

.. code-block:: php

    <?php
    /**
     * @OneToOne(targetEntity="Shipping")
     * @JoinColumn(name="shipping_id", referencedColumnName="id")
     */
    private $shipping;

The @JoinTable definition used for many-to-many mappings has
similar defaults. As an example, consider this mapping:

.. code-block:: php

    <?php
    class User
    {
        //...
        /** @ManyToMany(targetEntity="Group") */
        private $groups;
        //...
    }

This is essentially the same as the following, more verbose,
mapping:

.. code-block:: php

    <?php
    class User
    {
        //...
        /**
         * @ManyToMany(targetEntity="Group")
         * @JoinTable(name="User_Group",
         *      joinColumns={@JoinColumn(name="User_id", referencedColumnName="id")},
         *      inverseJoinColumns={@JoinColumn(name="Group_id", referencedColumnName="id")}
         *      )
         */
        private $groups;
        //...
    }

In that case, the name of the join table defaults to a combination
of the simple, unqualified class names of the participating
classes, separated by an underscore character. The names of the
join columns default to the simple, unqualified class name of the
targeted class followed by "\_id". The referencedColumnName always
defaults to "id", just as in one-to-one or many-to-one mappings.

If you accept these defaults, you can reduce the mapping code to a
minimum.

Initializing Collections
------------------------

You have to be careful when using entity fields that contain a
collection of related entities. Say we have a User entity that
contains a collection of groups:

.. code-block:: php

    <?php
    /** @Entity */
    class User
    {
        /** @ManyToMany(targetEntity="Group") */
        private $groups;
    
        public function getGroups()
        {
            return $this->groups;
        }
    }

With this code alone the ``$groups`` field only contains an
instance of ``Doctrine\Common\Collections\Collection`` if the user
is retrieved from Doctrine, however not after you instantiated a
fresh instance of the User. When your user entity is still new
``$groups`` will obviously be null.

This is why we recommend to initialize all collection fields to an
empty ``ArrayCollection`` in your entities constructor:

.. code-block:: php

    <?php
    use Doctrine\Common\Collections\ArrayCollection;
    
    /** @Entity */
    class User
    {
        /** @ManyToMany(targetEntity="Group") */
        private $groups;
    
        public function __construct()
        {
            $this->groups = new ArrayCollection();
        }
    
        public function getGroups()
        {
            return $this->groups;
        }
    }

Now the following code will be working even if the Entity hasn't
been associated with an EntityManager yet:

.. code-block:: php

    <?php
    $group = $entityManager->find('Group', $groupId);
    $user = new User();
    $user->getGroups()->add($group);

Runtime vs Development Mapping Validation
-----------------------------------------

For performance reasons Doctrine 2 has to skip some of the
necessary validation of association mappings. You have to execute
this validation in your development workflow to verify the
associations are correctly defined.

You can either use the Doctrine Command Line Tool:

.. code-block:: php

    doctrine orm:validate-schema

Or you can trigger the validation manually:

.. code-block:: php

    use Doctrine\ORM\Tools\SchemaValidator;
    
    $validator = new SchemaValidator($entityManager);
    $errors = $validator->validateMapping();
    
    if (count($errors) > 0) {
        // Lots of errors!
        echo implode("\n\n", $errors);
    }

If the mapping is invalid the errors array contains a positive
number of elements with error messages.

.. note::

    One common error is to use a backlash in front of the
    fully-qualified class-name. Whenever a FQCN is represented inside a
    string (such as in your mapping definitions) you have to drop the
    prefix backslash. PHP does this with ``get_class()`` or Reflection
    methods for backwards compatibility reasons.


One-To-One, Unidirectional
--------------------------

A unidirectional one-to-one association is very common. Here is an
example of a ``Product`` that has one ``Shipping`` object
associated to it. The ``Shipping`` side does not reference back to
the ``Product`` so it is unidirectional.

.. code-block:: php

    <?php
    /** @Entity */
    class Product
    {
        // ...
    
        /**
         * @OneToOne(targetEntity="Shipping")
         * @JoinColumn(name="shipping_id", referencedColumnName="id")
         */
        private $shipping;
    
        // ...
    }
    
    /** @Entity */
    class Shipping
    {
        // ...
    }

Note that the @JoinColumn is not really necessary in this example,
as the defaults would be the same.

Generated MySQL Schema:

.. code-block:: sql

    CREATE TABLE Product (
        id INT AUTO_INCREMENT NOT NULL,
        shipping_id INT DEFAULT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    CREATE TABLE Shipping (
        id INT AUTO_INCREMENT NOT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    ALTER TABLE Product ADD FOREIGN KEY (shipping_id) REFERENCES Shipping(id);

One-To-One, Bidirectional
-------------------------

Here is a one-to-one relationship between a ``Customer`` and a
``Cart``. The ``Cart`` has a reference back to the ``Customer`` so
it is bidirectional.

.. code-block:: php

    <?php
    /** @Entity */
    class Customer
    {
        // ...
    
        /**
         * @OneToOne(targetEntity="Cart", mappedBy="customer")
         */
        private $cart;
    
        // ...
    }
    
    /** @Entity */
    class Cart
    {
        // ...
    
        /**
         * @OneToOne(targetEntity="Customer", inversedBy="cart")
         * @JoinColumn(name="customer_id", referencedColumnName="id")
         */
        private $customer;
    
        // ...
    }

Note that the @JoinColumn is not really necessary in this example,
as the defaults would be the same.

Generated MySQL Schema:

.. code-block:: sql

    CREATE TABLE Cart (
        id INT AUTO_INCREMENT NOT NULL,
        customer_id INT DEFAULT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    CREATE TABLE Customer (
        id INT AUTO_INCREMENT NOT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    ALTER TABLE Cart ADD FOREIGN KEY (customer_id) REFERENCES Customer(id);

See how the foreign key is defined on the owning side of the
relation, the table ``Cart``.

One-To-One, Self-referencing
----------------------------

You can easily have self referencing one-to-one relationships like
below.

.. code-block:: php

    <?php
    /** @Entity */
    class Student
    {
        // ...
    
        /**
         * @OneToOne(targetEntity="Student")
         * @JoinColumn(name="mentor_id", referencedColumnName="id")
         */
        private $mentor;
    
        // ...
    }

Note that the @JoinColumn is not really necessary in this example,
as the defaults would be the same.

With the generated MySQL Schema:

.. code-block:: sql

    CREATE TABLE Student (
        id INT AUTO_INCREMENT NOT NULL,
        mentor_id INT DEFAULT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    ALTER TABLE Student ADD FOREIGN KEY (mentor_id) REFERENCES Student(id);

One-To-Many, Unidirectional with Join Table
-------------------------------------------

A unidirectional one-to-many association can be mapped through a
join table. From Doctrine's point of view, it is simply mapped as a
unidirectional many-to-many whereby a unique constraint on one of
the join columns enforces the one-to-many cardinality. The
following example sets up such a unidirectional one-to-many
association:

.. code-block:: php

    <?php
    /** @Entity */
    class User
    {
        // ...
    
        /**
         * @ManyToMany(targetEntity="Phonenumber")
         * @JoinTable(name="users_phonenumbers",
         *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
         *      inverseJoinColumns={@JoinColumn(name="phonenumber_id", referencedColumnName="id", unique=true)}
         *      )
         */
        private $phonenumbers;
    
        public function __construct() {
            $this->phonenumbers = new \Doctrine\Common\Collections\ArrayCollection();
        }
    
        // ...
    }
    
    /** @Entity */
    class Phonenumber
    {
        // ...
    }

.. note::

    One-To-Many uni-directional relations with join-table only
    work using the @ManyToMany annotation and a unique-constraint.


Generates the following MySQL Schema:

.. code-block:: sql

    CREATE TABLE User (
        id INT AUTO_INCREMENT NOT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    
    CREATE TABLE users_phonenumbers (
        user_id INT NOT NULL,
        phonenumber_id INT NOT NULL,
        UNIQUE INDEX users_phonenumbers_phonenumber_id_uniq (phonenumber_id),
        PRIMARY KEY(user_id, phonenumber_id)
    ) ENGINE = InnoDB;
    
    CREATE TABLE Phonenumber (
        id INT AUTO_INCREMENT NOT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    
    ALTER TABLE users_phonenumbers ADD FOREIGN KEY (user_id) REFERENCES User(id);
    ALTER TABLE users_phonenumbers ADD FOREIGN KEY (phonenumber_id) REFERENCES Phonenumber(id);

Many-To-One, Unidirectional
---------------------------

You can easily implement a many-to-one unidirectional association
with the following:

.. code-block:: php

    <?php
    /** @Entity */
    class User
    {
        // ...
    
        /**
         * @ManyToOne(targetEntity="Address")
         * @JoinColumn(name="address_id", referencedColumnName="id")
         */
        private $address;
    }
    
    /** @Entity */
    class Address
    {
        // ...
    }

.. note::

    The above ``@JoinColumn`` is optional as it would default
    to ``address_id`` and ``id`` anyways. You can omit it and let it
    use the defaults.


Generated MySQL Schema:

.. code-block:: sql

    CREATE TABLE User (
        id INT AUTO_INCREMENT NOT NULL,
        address_id INT DEFAULT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    
    CREATE TABLE Address (
        id INT AUTO_INCREMENT NOT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    
    ALTER TABLE User ADD FOREIGN KEY (address_id) REFERENCES Address(id);

One-To-Many, Bidirectional
--------------------------

Bidirectional one-to-many associations are very common. The
following code shows an example with a Product and a Feature
class:

.. code-block:: php

    <?php
    /** @Entity */
    class Product
    {
        // ...
        /**
         * @OneToMany(targetEntity="Feature", mappedBy="product")
         */
        private $features;
        // ...
    
        public function __construct() {
            $this->features = new \Doctrine\Common\Collections\ArrayCollection();
        }
    }
    
    /** @Entity */
    class Feature
    {
        // ...
        /**
         * @ManyToOne(targetEntity="Product", inversedBy="features")
         * @JoinColumn(name="product_id", referencedColumnName="id")
         */
        private $product;
        // ...
    }

Note that the @JoinColumn is not really necessary in this example,
as the defaults would be the same.

Generated MySQL Schema:

.. code-block:: sql

    CREATE TABLE Product (
        id INT AUTO_INCREMENT NOT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    CREATE TABLE Feature (
        id INT AUTO_INCREMENT NOT NULL,
        product_id INT DEFAULT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    ALTER TABLE Feature ADD FOREIGN KEY (product_id) REFERENCES Product(id);

One-To-Many, Self-referencing
-----------------------------

You can also setup a one-to-many association that is
self-referencing. In this example we setup a hierarchy of
``Category`` objects by creating a self referencing relationship.
This effectively models a hierarchy of categories and from the
database perspective is known as an adjacency list approach.

.. code-block:: php

    <?php
    /** @Entity */
    class Category
    {
        // ...
        /**
         * @OneToMany(targetEntity="Category", mappedBy="parent")
         */
        private $children;
    
        /**
         * @ManyToOne(targetEntity="Category", inversedBy="children")
         * @JoinColumn(name="parent_id", referencedColumnName="id")
         */
        private $parent;
        // ...
    
        public function __construct() {
            $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        }
    }

Note that the @JoinColumn is not really necessary in this example,
as the defaults would be the same.

Generated MySQL Schema:

.. code-block:: sql

    CREATE TABLE Category (
        id INT AUTO_INCREMENT NOT NULL,
        parent_id INT DEFAULT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    ALTER TABLE Category ADD FOREIGN KEY (parent_id) REFERENCES Category(id);

Many-To-Many, Unidirectional
----------------------------

Real many-to-many associations are less common. The following
example shows a unidirectional association between User and Group
entities:

.. code-block:: php

    <?php
    /** @Entity */
    class User
    {
        // ...
    
        /**
         * @ManyToMany(targetEntity="Group")
         * @JoinTable(name="users_groups",
         *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
         *      inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
         *      )
         */
        private $groups;
    
        // ...
    
        public function __construct() {
            $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
        }
    }
    
    /** @Entity */
    class Group
    {
        // ...
    }

    **NOTE** Why are many-to-many associations less common? Because
    frequently you want to associate additional attributes with an
    association, in which case you introduce an association class.
    Consequently, the direct many-to-many association disappears and is
    replaced by one-to-many/many-to-one associations between the 3
    participating classes.


Generated MySQL Schema:

.. code-block:: sql

    CREATE TABLE User (
        id INT AUTO_INCREMENT NOT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    CREATE TABLE users_groups (
        user_id INT NOT NULL,
        group_id INT NOT NULL,
        PRIMARY KEY(user_id, group_id)
    ) ENGINE = InnoDB;
    CREATE TABLE Group (
        id INT AUTO_INCREMENT NOT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    ALTER TABLE users_groups ADD FOREIGN KEY (user_id) REFERENCES User(id);
    ALTER TABLE users_groups ADD FOREIGN KEY (group_id) REFERENCES Group(id);

Many-To-Many, Bidirectional
---------------------------

Here is a similar many-to-many relationship as above except this
one is bidirectional.

.. code-block:: php

    <?php
    /** @Entity */
    class User
    {
        // ...
    
        /**
         * @ManyToMany(targetEntity="Group", inversedBy="users")
         * @JoinTable(name="users_groups",
         *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
         *      inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
         *      )
         */
        private $groups;
    
        public function __construct() {
            $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
        }
    
        // ...
    }
    
    /** @Entity */
    class Group
    {
        // ...
        /**
         * @ManyToMany(targetEntity="User", mappedBy="groups")
         */
        private $users;
    
        public function __construct() {
            $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        }
    
        // ...
    }

The MySQL schema is exactly the same as for the Many-To-Many
uni-directional case above.

Picking Owning and Inverse Side
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

For Many-To-Many associations you can chose which entity is the
owning and which the inverse side. There is a very simple semantic
rule to decide which side is more suitable to be the owning side
from a developers perspective. You only have to ask yourself, which
entity is responsible for the connection management and pick that
as the owning side.

Take an example of two entities ``Article`` and ``Tag``. Whenever
you want to connect an Article to a Tag and vice-versa, it is
mostly the Article that is responsible for this relation. Whenever
you add a new article, you want to connect it with existing or new
tags. Your create Article form will probably support this notion
and allow to specify the tags directly. This is why you should pick
the Article as owning side, as it makes the code more
understandable:

.. code-block:: php

    <?php
    class Article
    {
        private $tags;
    
        public function addTag(Tag $tag)
        {
            $tag->addArticle($this); // synchronously updating inverse side
            $this->tags[] = $tag;
        }
    }
    
    class Tag
    {
        private $articles;
    
        public function addArticle(Article $article)
        {
            $this->articles[] = $article;
        }
    }

This allows to group the tag adding on the ``Article`` side of the
association:

.. code-block:: php

    <?php
    $article = new Article();
    $article->addTag($tagA);
    $article->addTag($tagB);

Many-To-Many, Self-referencing
------------------------------

You can even have a self-referencing many-to-many association. A
common scenario is where a ``User`` has friends and the target
entity of that relationship is a ``User`` so it is self
referencing. In this example it is bidirectional so ``User`` has a
field named ``$friendsWithMe`` and ``$myFriends``.

.. code-block:: php

    <?php
    /** @Entity */
    class User
    {
        // ...
    
        /**
         * @ManyToMany(targetEntity="User", mappedBy="myFriends")
         */
        private $friendsWithMe;
    
        /**
         * @ManyToMany(targetEntity="User", inversedBy="friendsWithMe")
         * @JoinTable(name="friends",
         *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
         *      inverseJoinColumns={@JoinColumn(name="friend_user_id", referencedColumnName="id")}
         *      )
         */
        private $myFriends;
    
        public function __construct() {
            $this->friendsWithMe = new \Doctrine\Common\Collections\ArrayCollection();
            $this->myFriends = new \Doctrine\Common\Collections\ArrayCollection();
        }
    
        // ...
    }

Generated MySQL Schema:

.. code-block:: sql

    CREATE TABLE User (
        id INT AUTO_INCREMENT NOT NULL,
        PRIMARY KEY(id)
    ) ENGINE = InnoDB;
    CREATE TABLE friends (
        user_id INT NOT NULL,
        friend_user_id INT NOT NULL,
        PRIMARY KEY(user_id, friend_user_id)
    ) ENGINE = InnoDB;
    ALTER TABLE friends ADD FOREIGN KEY (user_id) REFERENCES User(id);
    ALTER TABLE friends ADD FOREIGN KEY (friend_user_id) REFERENCES User(id);

Ordering To-Many Collections
----------------------------

In many use-cases you will want to sort collections when they are
retrieved from the database. In userland you do this as long as you
haven't initially saved an entity with its associations into the
database. To retrieve a sorted collection from the database you can
use the ``@OrderBy`` annotation with an collection that specifies
an DQL snippet that is appended to all queries with this
collection.

Additional to any ``@OneToMany`` or ``@ManyToMany`` annotation you
can specify the ``@OrderBy`` in the following way:

.. code-block:: php

    <?php
    /** @Entity */
    class User
    {
        // ...
    
        /**
         * @ManyToMany(targetEntity="Group")
         * @OrderBy({"name" = "ASC"})
         */
        private $groups;
    }

The DQL Snippet in OrderBy is only allowed to consist of
unqualified, unquoted field names and of an optional ASC/DESC
positional statement. Multiple Fields are separated by a comma (,).
The referenced field names have to exist on the ``targetEntity``
class of the ``@ManyToMany`` or ``@OneToMany`` annotation.

The semantics of this feature can be described as follows.


-  ``@OrderBy`` acts as an implicit ORDER BY clause for the given
   fields, that is appended to all the explicitly given ORDER BY
   items.
-  All collections of the ordered type are always retrieved in an
   ordered fashion.
-  To keep the database impact low, these implicit ORDER BY items
   are only added to an DQL Query if the collection is fetch joined in
   the DQL query.

Given our previously defined example, the following would not add
ORDER BY, since g is not fetch joined:

.. code-block:: sql

    SELECT u FROM User u JOIN u.groups g WHERE SIZE(g) > 10

However the following:

.. code-block:: sql

    SELECT u, g FROM User u JOIN u.groups g WHERE u.id = 10

...would internally be rewritten to:

.. code-block:: sql

    SELECT u, g FROM User u JOIN u.groups g WHERE u.id = 10 ORDER BY g.name ASC

You can't reverse the order with an explicit DQL ORDER BY:

.. code-block:: sql

    SELECT u, g FROM User u JOIN u.groups g WHERE u.id = 10 ORDER BY g.name DESC

...is internally rewritten to:

.. code-block:: sql

    SELECT u, g FROM User u JOIN u.groups g WHERE u.id = 10 ORDER BY g.name DESC, g.name ASC


