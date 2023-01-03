Composite and Foreign Keys as Primary Key
=========================================

Doctrine ORM supports composite primary keys natively. Composite keys are a very powerful relational database concept
and we took good care to make sure Doctrine ORM supports as many of the composite primary key use-cases.
For Doctrine ORM composite keys of primitive data-types are supported, even foreign keys as
primary keys are supported.

This tutorial shows how the semantics of composite primary keys work and how they map to the database.

General Considerations
~~~~~~~~~~~~~~~~~~~~~~

Every entity with a composite key cannot use an id generator other than "NONE". That means
the ID fields have to have their values set before you call ``EntityManager#persist($entity)``.

Primitive Types only
~~~~~~~~~~~~~~~~~~~~

You can have composite keys as long as they only consist of the primitive types
``integer`` and ``string``. Suppose you want to create a database of cars and use the model-name
and year of production as primary keys:

.. configuration-block::

    .. code-block:: attribute

        <?php
        namespace VehicleCatalogue\Model;

        #[Entity]
        class Car
        {
            public function __construct(
                #[Id, Column]
                private string $name,
                #[Id, Column]
                private int $year,
            ) {
            }

            public function getModelName(): string
            {
                return $this->name;
            }

            public function getYearOfProduction(): int
            {
                return $this->year;
            }
        }

    .. code-block:: annotation

        <?php
        namespace VehicleCatalogue\Model;

        /**
         * @Entity
         */
        class Car
        {
            /** @Id @Column(type="string") */
            private string $name;
            /** @Id @Column(type="integer") */
            private int $year;

            public function __construct($name, $year)
            {
                $this->name = $name;
                $this->year = $year;
            }

            public function getModelName(): string
            {
                return $this->name;
            }

            public function getYearOfProduction(): int
            {
                return $this->year;
            }
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mapping xmlns="https://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="https://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="https://doctrine-project.org/schemas/orm/doctrine-mapping
                                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

            <entity name="VehicleCatalogue\Model\Car">
                <id field="name" type="string" />
                <id field="year" type="integer" />
            </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        VehicleCatalogue\Model\Car:
          type: entity
          id:
            name:
              type: string
            year:
              type: integer

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
               ->setParameter(1, ["name" => "Audi A8", "year" => 2010])
               ->getSingleResult();

You can also use this entity in associations. Doctrine will then generate two foreign keys one for ``name``
and to ``year`` to the related entities.

.. note::

    This example shows how you can nicely solve the requirement for existing
    values before ``EntityManager#persist()``: By adding them as mandatory values for the constructor.

Identity through foreign Entities
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

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
-   Plug an ``#[Id]`` attribute onto every association.
-   Set an attribute ``association-key`` with the field name of the association in XML.
-   Set a key ``associationKey:`` with the field name of the association in YAML.

Use-Case 1: Dynamic Attributes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

We keep up the example of an Article with arbitrary attributes, the mapping looks like this:

.. configuration-block::

    .. code-block:: attribute

        <?php
        namespace Application\Model;

        use Doctrine\Common\Collections\ArrayCollection;

        #[Entity]
        class Article
        {
            #[Id, Column, GeneratedValue]
            private int|null $id = null;
            #[Column]
            private string $title;

            /** @var ArrayCollection<string, ArticleAttribute> */
            #[OneToMany(targetEntity: ArticleAttribute::class, mappedBy: 'article', cascade: ['ALL'], indexBy: 'attribute')]
            private Collection $attributes;

            public function addAttribute(string $name, ArticleAttribute $value): void
            {
                $this->attributes[$name] = new ArticleAttribute($name, $value, $this);
            }
        }

        #[Entity]
        class ArticleAttribute
        {
            #[Id, ManyToOne(targetEntity: Article::class, inversedBy: 'attributes')]
            private Article $article;

            #[Id, Column]
            private string $attribute;

            #[Column]
            private string $value;

            public function __construct(string $name, string $value, Article $article)
            {
                $this->attribute = $name;
                $this->value = $value;
                $this->article = $article;
            }
        }

    .. code-block:: annotation

        <?php
        namespace Application\Model;

        use Doctrine\Common\Collections\ArrayCollection;

        /**
         * @Entity
         */
        class Article
        {
            /** @Id @Column(type="integer") @GeneratedValue */
            private int|null $id = null;
            /** @Column(type="string") */
            private string $title;

            /**
             * @OneToMany(targetEntity="ArticleAttribute", mappedBy="article", cascade={"ALL"}, indexBy="attribute")
             * @var Collection<int, ArticleAttribute>
             */
            private Collection $attributes;

            public function addAttribute($name, $value): void
            {
                $this->attributes[$name] = new ArticleAttribute($name, $value, $this);
            }
        }

        /**
         * @Entity
         */
        class ArticleAttribute
        {
            /** @Id @ManyToOne(targetEntity="Article", inversedBy="attributes") */
            private Article|null $article;

            /** @Id @Column(type="string") */
            private string $attribute;

            /** @Column(type="string") */
            private string $value;

            public function __construct($name, $value, $article)
            {
                $this->attribute = $name;
                $this->value = $value;
                $this->article = $article;
            }
        }

    .. code-block:: xml

        <doctrine-mapping xmlns="https://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="https://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="https://doctrine-project.org/schemas/orm/doctrine-mapping
                                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

             <entity name="Application\Model\ArticleAttribute">
                <id name="article" association-key="true" />
                <id name="attribute" type="string" />

                <field name="value" type="string" />

                <many-to-one field="article" target-entity="Article" inversed-by="attributes" />
             <entity>

        </doctrine-mapping>

    .. code-block:: yaml

        Application\Model\ArticleAttribute:
          type: entity
          id:
            article:
              associationKey: true
            attribute:
              type: string
          fields:
            value:
              type: string
          manyToOne:
            article:
              targetEntity: Article
              inversedBy: attributes


Use-Case 2: Simple Derived Identity
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Sometimes you have the requirement that two objects are related by a One-To-One association
and that the dependent class should re-use the primary key of the class it depends on.
One good example for this is a user-address relationship:

.. configuration-block::

    .. code-block:: attribute

        <?php

        #[Entity]
        class User
        {
            #[Id, Column, GeneratedValue]
            private int|null $id = null;
        }

        #[Entity]
        class Address
        {
            #[Id, OneToOne(targetEntity: User::class)]
            private User|null $user = null;
        }

    .. code-block:: yaml

        User:
          type: entity
          id:
            id:
              type: integer
              generator:
                strategy: AUTO

        Address:
          type: entity
          id:
            user:
              associationKey: true
          oneToOne:
            user:
              targetEntity: User


Use-Case 3: Join-Table with Metadata
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In the classic order product shop example there is the concept of the order item
which contains references to order and product and additional data such as the amount
of products purchased and maybe even the current price.

.. code-block:: php

    <?php

    use DateTime;
    use Doctrine\Common\Collections\ArrayCollection;

    #[Entity]
    class Order
    {
        #[Id, Column, GeneratedValue]
        private int|null $id = null;

        /** @var ArrayCollection<int, OrderItem> */
        #[OneToMany(targetEntity: OrderItem::class, mappedBy: 'order')]
        private Collection $items;

        #[Column]
        private bool $paid = false;
        #[Column]
        private bool $shipped = false;
        #[Column]
        private DateTime $created;

        public function __construct(
            #[ManyToOne(targetEntity: Customer::class)]
            private Customer $customer,
        ) {
            $this->items = new ArrayCollection();
            $this->created = new DateTime("now");
        }
    }

    #[Entity]
    class Product
    {
        #[Id, Column, GeneratedValue]
        private int|null $id = null;

        #[Column]
        private string $name;

        #[Column]
        private int $currentPrice;

        public function getCurrentPrice(): int
        {
            return $this->currentPrice;
        }
    }

    #[Entity]
    class OrderItem
    {
        #[Id, ManyToOne(targetEntity: Order::class)]
        private Order|null $order = null;

        #[Id, ManyToOne(targetEntity: Product::class)]
        private Product|null $product = null;

        #[Column]
        private int $amount = 1;

        #[Column]
        private int $offeredPrice;

        public function __construct(Order $order, Product $product, int $amount = 1)
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
