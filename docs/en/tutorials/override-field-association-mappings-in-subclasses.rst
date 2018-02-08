Override Field Association Mappings In Subclasses
-------------------------------------------------

Sometimes there is a need to persist entities but override all or part of the
mapping metadata. Sometimes also the mapping to override comes from entities
using traits where the traits have mapping metadata.
This tutorial explains how to override mapping metadata,
i.e. attributes and associations metadata in particular. The example here shows
the overriding of a class that uses a trait but is similar when extending a base
class as shown at the end of this tutorial.

Suppose we have a class ExampleEntityWithOverride. This class uses trait ExampleTrait:

.. code-block:: php

    <?php

    use Doctrine\ORM\Annotation as ORM;

    /**
     * @ORM\Entity
     *
     * @ORM\AttributeOverrides({
     *      @ORM\AttributeOverride(name="foo",
     *          column=@ORM\Column(
     *              name     = "foo_overridden",
     *              type     = "integer",
     *              length   = 140,
     *              nullable = false,
     *              unique   = false
     *          )
     *      )
     * })
     *
     * @ORM\AssociationOverrides({
     *      @ORM\AssociationOverride(name="bar",
     *          joinColumns=@ORM\JoinColumn(
     *              name="example_entity_overridden_bar_id", referencedColumnName="id"
     *          )
     *      )
     * })
     */
    class ExampleEntityWithOverride
    {
        use ExampleTrait;
    }

    /**
     * @ORM\Entity
     */
    class Bar
    {
        /** @ORM\Id @ORM\Column(type="string") */
        private $id;
    }

The docblock is showing metadata override of the attribute and association type. It
basically changes the names of the columns mapped for a property ``foo`` and for
the association ``bar`` which relates to Bar class shown above. Here is the trait
which has mapping metadata that is overridden by the annotation above:

.. code-block:: php

    <?php

    use Doctrine\ORM\Annotation as ORM;

    /**
     * Trait class
     */
    trait ExampleTrait
    {
        /** @ORM\Id @ORM\Column(type="string") */
        private $id;

        /**
         * @ORM\Column(name="trait_foo", type="integer", length=100, nullable=true, unique=true)
         */
        protected $foo;

        /**
         * @ORM\OneToOne(targetEntity="Bar", cascade={"persist", "refresh"})
         * @ORM\JoinColumn(name="example_trait_bar_id", referencedColumnName="id")
         */
        protected $bar;
    }

The case for just extending a class would be just the same but:

.. code-block:: php

    <?php
    class ExampleEntityWithOverride extends BaseEntityWithSomeMapping
    {
        // ...
    }

Overriding is also supported via XML (:ref:`examples <inheritence_mapping_overrides>`).
