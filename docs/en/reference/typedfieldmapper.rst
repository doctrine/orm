Implementing a TypedFieldMapper
===============================

.. versionadded:: 2.14

You can specify custom typed field mapping between PHP type and DBAL type using ``Doctrine\ORM\Configuration``
and a custom ``Doctrine\ORM\Mapping\TypedFieldMapper`` implementation.

.. code-block:: php

    <?php
    $configuration->setTypedFieldMapper(new CustomTypedFieldMapper());


DefaultTypedFieldMapper
-----------------------

By default the ``Doctrine\ORM\Mapping\DefaultTypedFieldMapper`` is used, and you can pass an array of
PHP type => DBAL type mappings into its constructor to override the default behavior or add new mappings.

.. code-block:: php

    <?php
    use App\CustomIds\CustomIdObject;
    use App\DBAL\Type\CustomIdObjectType;
    use Doctrine\ORM\Mapping\DefaultTypedFieldMapper;

    $configuration->setTypedFieldMapper(new DefaultTypedFieldMapper([
        CustomIdObject::class => CustomIdObjectType::class,
    ]));

Then, an entity using the ``CustomIdObject`` typed field will be correctly assigned its DBAL type
(``CustomIdObjectType``) without the need of explicit declaration.

.. configuration-block::

    .. code-block:: attribute

        <?php
        #[ORM\Entity]
        #[ORM\Table(name: 'cms_users_typed_with_custom_typed_field')]
        class UserTypedWithCustomTypedField
        {
            #[ORM\Column]
            public CustomIdObject $customId;

            // ...
        }

    .. code-block:: xml

        <doctrine-mapping>
          <entity name="UserTypedWithCustomTypedField">
            <field name="customId"/>
            <!-- -->
          </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        UserTypedWithCustomTypedField:
          type: entity
          fields:
            customId: ~

It is perfectly valid to override even the "automatic" mapping rules mentioned above:

.. code-block:: php

    <?php
    use App\DBAL\Type\CustomIntType;
    use Doctrine\ORM\Mapping\DefaultTypedFieldMapper;

    $configuration->setTypedFieldMapper(new DefaultTypedFieldMapper([
        'int' => CustomIntType::class,
    ]));

.. note::

    If chained, once the first ``TypedFieldMapper`` assigns a type to a field, the ``DefaultTypedFieldMapper`` will
    ignore its mapping and not override it anymore (if it is later in the chain). See below for chaining type mappers.


TypedFieldMapper interface
-------------------------
The interface ``Doctrine\ORM\Mapping\TypedFieldMapper`` allows you to implement your own
typed field mapping logic. It consists of just one function


.. code-block:: php

    <?php
    /**
     * Validates & completes the given field mapping based on typed property.
     *
     * @param array{fieldName: string, enumType?: string, type?: mixed}  $mapping The field mapping to validate & complete.
     * @param \ReflectionProperty                                        $field
     *
     * @return array{fieldName: string, enumType?: string, type?: mixed} The updated mapping.
     */
    public function validateAndComplete(array $mapping, ReflectionProperty $field): array;


ChainTypedFieldMapper
---------------------

The class ``Doctrine\ORM\Mapping\ChainTypedFieldMapper`` allows you to chain multiple ``TypedFieldMapper`` instances.
When being evaluated, the ``TypedFieldMapper::validateAndComplete`` is called in the order in which
the instances were supplied to the ``ChainTypedFieldMapper`` constructor.

.. code-block:: php

    <?php
    use App\DBAL\Type\CustomIntType;
    use Doctrine\ORM\Mapping\ChainTypedFieldMapper;
    use Doctrine\ORM\Mapping\DefaultTypedFieldMapper;

    $configuration->setTypedFieldMapper(
        new ChainTypedFieldMapper(
            new DefaultTypedFieldMapper(['int' => CustomIntType::class,]),
            new CustomTypedFieldMapper()
        )
    );


Implementing a TypedFieldMapper
-------------------------------

If you want to assign all ``BackedEnum`` fields to your custom ``BackedEnumDBALType`` or you want to use different
DBAL types based on whether the entity field is nullable or not, you can achieve this by implementing your own
typed field mapper.

You need to create a class which implements ``Doctrine\ORM\Mapping\TypedFieldMapper``.

.. code-block:: php

    <?php
    final class CustomEnumTypedFieldMapper implements TypedFieldMapper
    {
        /**
         * {@inheritDoc}
         */
        public function validateAndComplete(array $mapping, ReflectionProperty $field): array
        {
            $type = $field->getType();

            if (
                ! isset($mapping['type'])
                && ($type instanceof ReflectionNamedType)
            ) {
                if (! $type->isBuiltin() && enum_exists($type->getName())) {
                    $mapping['type'] = BackedEnumDBALType::class;
                }
            }

            return $mapping;
        }
    }

.. note::

    Note that this case checks whether the mapping is already assigned, and if yes, it skips it. This is up to your
    implementation. You can make a "greedy" mapper which will always override the mapping with its own type, or one
    that behaves like the ``DefaultTypedFieldMapper`` and does not modify the type once its set prior in the chain.
