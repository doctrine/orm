Composite and Foreign Keys as Primary Key
=========================================

.. versionadded:: 2.1

Doctrine 2 supports composite primary keys natively. Composite keys are a very powerful relational database concept
and we took good care to make sure Doctrine 2 supports as many of the composite primary key use-cases.
For Doctrine 2.0 composite keys of primitive data-types are supported, for Doctrine 2.1 even foreign keys as
primary keys are supported.

This tutorial shows how the semantics of composite primary keys work and how they map to the database.

General Considerations
~~~~~~~~~~~~~~~~~~~~~~

Every entity with a composite key cannot use an id generator other than "NONE". That means
the ID fields have to have their values set before you call ``EntityManager#persist($entity)``.

Primitive Types only
~~~~~~~~~~~~~~~~~~~~

Even in version 2.0 you can have composite keys as long as they only consist of the primitive types
``integer`` and ``string``. Suppose you want to create a database of cars and use the model-name
and year of production as primary keys:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace VehicleCatalogue\Model;

        use Doctrine\ORM\Annotation as ORM;

        /**
         * @ORM\Entity
         */
        class Car
        {
            /** @ORM\Id @ORM\Column(type="string") */
            private $name;
            /** @ORM\Id @ORM\Column(type="integer") */
            private $year;

            public function __construct($name, $year)
            {
                $this->name = $name;
                $this->year = $year;
            }

            public function getModelName()
            {
                return $this->name;
            }

            public function getYearOfProduction()
            {
                return $this->year;
            }
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                  http://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

            <entity name="VehicleCatalogue\Model\Car">
                <id field="name" type="string" />
                <id field="year" type="integer" />
            </entity>
        </doctrine-mapping>

Now you can use this entity:

.. code-block:: php

    <?php
    namespace VehicleCatalogue\Model;

    // $em is the EntityManager

    $car = new Car("Audi A8", 2010);
    $em->persist($car);
    $em->flush();

And for querying you can use arrays to both DQL and EntityRepositories:

.. code-block:: php

    <?php
    namespace VehicleCatalogue\Model;

    // $em is the EntityManager
    $audi = $em->find("VehicleCatalogue\Model\Car", array("name" => "Audi A8", "year" => 2010));

    $dql = "SELECT c FROM VehicleCatalogue\Model\Car c WHERE c.id = ?1";
    $audi = $em->createQuery($dql)
               ->setParameter(1, array("name" => "Audi A8", "year" => 2010))
               ->getSingleResult();

You can also use this entity in associations. Doctrine will then generate two foreign keys one for ``name``
and to ``year`` to the related entities.

.. note::

    This example shows how you can nicely solve the requirement for existing
    values before ``EntityManager#persist()``: By adding them as mandatory values for the constructor.

Identity through foreign Entities
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. note::

    Identity through foreign entities is only supported with Doctrine 2.1

There are tons of use-cases where the identity of an Entity should be determined by the entity
of one or many parent entities.

-   Dynamic Attributes of an Entity (for example Article). Each Article has many
    attributes with primary key "article_id" and "attribute_name".
-   Address object of a Person, the primary key of the address is "user_id". This is not a case of a composite primary
    key, but the identity is derived through a foreign entity and a foreign key.
-   Join Tables with metadata can be modelled as Entity, for example connections between two articles
    with a little description and a score.

The semantics of mapping identity through foreign entities are easy:

-   Only allowed on Many-To-One or One-To-One associations.
-   Plug an ``@ORM\Id`` annotation onto every association.
-   Set an attribute ``association-key`` with the field name of the association in XML.

Use-Case 1: Dynamic Attributes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

We keep up the example of an Article with arbitrary attributes, the mapping looks like this:

.. configuration-block::

    .. code-block:: php

        <?php
        namespace Application\Model;

        use Doctrine\Common\Collections\ArrayCollection;
        use Doctrine\ORM\Annotation as ORM;

        /**
         * @ORM\Entity
         */
        class Article
        {
            /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
            private $id;

            /** @ORM\Column(type="string") */
            private $title;

            /**
             * @ORM\OneToMany(targetEntity="ArticleAttribute", mappedBy="article", cascade={"ALL"}, indexBy="attribute")
             */
            private $attributes;

            public function addAttribute($name, $value)
            {
                $this->attributes[$name] = new ArticleAttribute($name, $value, $this);
            }
        }

        /**
         * @ORM\Entity
         */
        class ArticleAttribute
        {
            /** @ORM\Id @ORM\ManyToOne(targetEntity="Article", inversedBy="attributes") */
            private $article;

            /** @ORM\Id @ORM\Column(type="string") */
            private $attribute;

            /** @ORM\Column(type="string") */
            private $value;

            public function __construct($name, $value, $article)
            {
                $this->attribute = $name;
                $this->value = $value;
                $this->article = $article;
            }
        }

    .. code-block:: xml

        <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                            http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

             <entity name="Application\Model\ArticleAttribute">
                <id name="article" association-key="true" />
                <id name="attribute" type="string" />

                <field name="value" type="string" />

                <many-to-one field="article" target-entity="Article" inversed-by="attributes" />
             <entity>

        </doctrine-mapping>

Use-Case 2: Simple Derived Identity
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Sometimes you have the requirement that two objects are related by a One-To-One association
and that the dependent class should re-use the primary key of the class it depends on.
One good example for this is a user-address relationship:

.. configuration-block::

    .. code-block:: php

        <?php

        use Doctrine\ORM\Annotation as ORM;

        /**
         * @ORM\Entity
         */
        class User
        {
            /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
            private $id;
        }

        /**
         * @ORM\Entity
         */
        class Address
        {
            /** @ORM\Id @ORM\OneToOne(targetEntity="User") */
            private $user;
        }

Use-Case 3: Join-Table with Metadata
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In the classic order product shop example there is the concept of the order item
which contains references to order and product and additional data such as the amount
of products purchased and maybe even the current price.

.. code-block:: php

    <?php
    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\ORM\Annotation as ORM;

    /** @ORM\Entity */
    class Order
    {
        /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
        private $id;

        /** @ORM\ManyToOne(targetEntity="Customer") */
        private $customer;

        /** @ORM\OneToMany(targetEntity="OrderItem", mappedBy="order") */
        private $items;

        /** @ORM\Column(type="boolean") */
        private $paid = false;

        /** @ORM\Column(type="boolean") */
        private $shipped = false;

        /** @ORM\Column(type="datetime") */
        private $created;

        public function __construct(Customer $customer)
        {
            $this->customer = $customer;
            $this->items = new ArrayCollection();
            $this->created = new \DateTime("now");
        }
    }

    /** @ORM\Entity */
    class Product
    {
        /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
        private $id;

        /** @ORM\Column(type="string") */
        private $name;

        /** @ORM\Column(type="decimal") */
        private $currentPrice;

        public function getCurrentPrice()
        {
            return $this->currentPrice;
        }
    }

    /** @ORM\Entity */
    class OrderItem
    {
        /** @ORM\Id @ORM\ManyToOne(targetEntity="Order") */
        private $order;

        /** @ORM\Id @ORM\ManyToOne(targetEntity="Product") */
        private $product;

        /** @ORM\Column(type="integer") */
        private $amount = 1;

        /** @ORM\Column(type="decimal") */
        private $offeredPrice;

        public function __construct(Order $order, Product $product, $amount = 1)
        {
            $this->order = $order;
            $this->product = $product;
            $this->offeredPrice = $product->getCurrentPrice();
        }
    }

Performance Considerations
~~~~~~~~~~~~~~~~~~~~~~~~~~

Using composite keys always comes with a performance hit compared to using entities with
a simple surrogate key. This performance impact is mostly due to additional PHP code that is
necessary to handle this kind of keys, most notably when using derived identifiers.

On the SQL side there is not much overhead as no additional or unexpected queries have to be
executed to manage entities with derived foreign keys.
