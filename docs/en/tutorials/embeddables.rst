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

You can also use mapped fields of embedded classes in DQL queries, just
as if they were declared in the ``User`` class:

.. code-block:: sql

    SELECT u FROM User u WHERE u.address.city = :myCity

