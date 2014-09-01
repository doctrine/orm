Separating Concerns using Embeddables
-------------------------------------

Embeddables are classes which are not entities themself, but are embedded
in entities and can also be queried in DQL. You'll mostly want to use them
to reduce duplication or separating concerns.

For the purposes of this tutorial, we will assume that you have a ``User``
class in your application and you would like to store an address in
the ``User`` class. We will model the ``Address`` class as an embeddable
instead of simply adding the respective columns to the ``User`` class.

.. configuration-block::

    .. code-block:: php

        <?php

        /** @Entity */
        class User
        {
            /** @Embedded(class = "Address") */
            private $address;
        }

        /** @Embeddable */
        class Address
        {
            /** @Column(type = "string") */
            private $street;

            /** @Column(type = "string") */
            private $postalCode;

            /** @Column(type = "string") */
            private $city;

            /** @Column(type = "string") */
            private $country;
        }

    .. code-block:: xml

        <doctrine-mapping>
            <entity name="User">
                <embedded name="address" class="Address" />
            </entity>

            <embeddable name="Address">
                <field name="street" type="string" />
                <field name="postalCode" type="string" />
                <field name="city" type="string" />
                <field name="country" type="string" />
            </embeddable>
        </doctrine-mapping>

    .. code-block:: yaml

        User:
          type: entity
          embedded:
            address:
              class: Address

        Address:
          type: embeddable
          fields:
            street: { type: string }
            postalCode: { type: string }
            city: { type: string }
            country: { type: string }

In terms of your database schema, Doctrine will automatically inline all
columns from the ``Address`` class into the table of the ``User`` class,
just as if you had declared them directly there.

Column Prefixing
----------------

By default, Doctrine prefixes your columns by using the value object name.

You can change this behaviour in the following ways:

.. configuration-block::

    .. code-block:: php

        <?php

        // Default behaviour
        // Will name your columns by prefixing them with "address_"
        // Your columns will be named as:
        // "address_street", "address_postalCode" ...

        /** @Entity */
        class User
        {
            /** @Embedded(class = "Address") */
            private $address;
        }


        // Will name your columns by prefixing them with "prefix_"
        // Your columns will be named as:
        // "prefix_street", "prefix_postalCode" ...

        /** @Entity */
        class User
        {
            /** @Embedded(class = "Address", columnPrefix = "prefix_") */
            private $address;
        }

        // Will NOT prefix your columns
        // Your columns will be named as:
        // "street", "postalCode" ...

        /** @Entity */
        class User
        {
            /** @Embedded(class = "Address", columnPrefix = false) */
            private $address;
        }

    .. code-block:: xml

        <!-- Default behaviour -->
        <!-- Will name your columns by prefixing them with "address_" -->
        <entity name="User">
            <embedded name="address" class="Address" />
        </entity>

        <!-- Will name your columns by prefixing them with "prefix_" -->
        <entity name="User">
            <embedded name="address" class="Address" columnPrefix="prefix_" />
        </entity>

        <!-- Will NOT prefix your columns -->
        <entity name="User">
            <embedded name="address" class="Address" columnPrefix="false" />
        </entity>

    .. code-block:: yaml

        # Default behaviour
        # Will name your columns by prefixing them with "address_"
        User:
          type: entity
          embedded:
            address:
              class: Address

        # Will name your columns by prefixing them with "prefix_"
        User:
          type: entity
          embedded:
            address:
              class: Address
              columnPrefix: prefix_

        # Will NOT prefix your columns
        User:
          type: entity
          embedded:
            address:
              class: Address
              columnPrefix: false


DQL
---

You can also use mapped fields of embedded classes in DQL queries, just
as if they were declared in the ``User`` class:

.. code-block:: sql

    SELECT u FROM User u WHERE u.address.city = :myCity

