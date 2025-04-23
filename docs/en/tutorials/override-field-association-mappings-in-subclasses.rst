Override Field Association Mappings In Subclasses
-------------------------------------------------

Sometimes there is a need to persist entities but override all or part of the
mapping metadata. Sometimes also the mapping to override comes from entities
using traits where the traits have mapping metadata.
This tutorial explains how to override mapping metadata,
i.e. attributes and associations metadata in particular. The example here shows
the overriding of a class that uses a trait but is similar when extending a base
class as shown at the end of this tutorial.

Suppose we have a class ``ExampleEntityWithOverride``. This class uses trait ``ExampleTrait``:

.. code-block:: php

    <?php

    #[Entity]
    #[AttributeOverrides([
        new AttributeOverride('foo', new Column(
            name: 'foo_overridden',
            type: 'integer',
            length: 140,
            nullable: false,
            unique: false,
        )),
    ])]
    #[AssociationOverrides([
        new AssociationOverride('bar', [
            new JoinColumn(
                name: 'example_entity_overridden_bar_id',
                referencedColumnName: 'id',
            ),
        ]),
    ])]
    class ExampleEntityWithOverride
    {
        use ExampleTrait;
    }

    #[Entity]
    class Bar
    {
        #[Id, Column(type: 'string')]
        private $id;
    }

``#[AttributeOverrides]`` contains metadata override of the attribute and association type. It
basically changes the names of the columns mapped for a property ``foo`` and for
the association ``bar`` which relates to Bar class shown above. Here is the trait
which has mapping metadata that is overridden by the attribute above:

.. code-block:: php

    <?php
    /**
     * Trait class
     */
    trait ExampleTrait
    {
        #[Id, Column(type: 'integer')]
        private int|null $id = null;

        #[Column(name: 'trait_foo', type: 'integer', length: 100, nullable: true, unique: true)]
        protected int $foo;

        #[OneToOne(targetEntity: Bar::class, cascade: ['persist'])]
        #[JoinColumn(name: 'example_trait_bar_id', referencedColumnName: 'id')]
        protected Bar|null $bar = null;
    }

The case for just extending a class would be just the same but:

.. code-block:: php

    <?php
    class ExampleEntityWithOverride extends BaseEntityWithSomeMapping
    {
        // ...
    }

Overriding is also supported via XML (:ref:`examples <inheritence_mapping_overrides>`).
