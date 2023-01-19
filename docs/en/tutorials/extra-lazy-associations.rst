Extra Lazy Associations
=======================

.. versionadded:: 2.1

In many cases associations between entities can get pretty large. Even in a simple scenario like a blog.
where posts can be commented, you always have to assume that a post draws hundreds of comments.
In Doctrine ORM if you accessed an association it would always get loaded completely into memory. This
can lead to pretty serious performance problems, if your associations contain several hundreds or thousands
of entities.

Doctrine ORM includes a feature called **Extra Lazy** for associations. Associations
are marked as **Lazy** by default, which means the whole collection object for an association is populated
the first time its accessed. If you mark an association as extra lazy the following methods on collections
can be called without triggering a full load of the collection:

-  ``Collection#contains($entity)``
-  ``Collection#containsKey($key)``
-  ``Collection#count()``
-  ``Collection#get($key)``
-  ``Collection#slice($offset, $length = null)``

For each of the above methods the following semantics apply:

-  For each call, if the Collection is not yet loaded, issue a straight SELECT statement against the database.
-  For each call, if the collection is already loaded, fallback to the default functionality for lazy collections. No additional SELECT statements are executed.

Additionally even with Doctrine ORM the following methods do not trigger the collection load:

-  ``Collection#add($entity)``
-  ``Collection#offsetSet($key, $entity)`` - ArrayAccess with no specific key ``$coll[] = $entity``, it does
   not work when setting specific keys like ``$coll[0] = $entity``.

With extra lazy collections you can now not only add entities to large collections but also paginate them
easily using a combination of ``count`` and ``slice``.


.. warning::

   ``removeElement`` directly issued DELETE queries to the database from
   version 2.4.0 to 2.7.0.  This circumvents the flush operation and might run
   outside a transactional boundary if you don't create one yourself. We
   consider this a critical bug in the assumption of how the ORM works and
   reverted ``removeElement`` EXTRA_LAZY behavior in 2.7.1.


Enabling Extra-Lazy Associations
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The mapping configuration is simple. Instead of using the default value of ``fetch="LAZY"`` you have to
switch to extra lazy as shown in these examples:

.. configuration-block::

    .. code-block:: attribute

        <?php
        namespace Doctrine\Tests\Models\CMS;

        #[Entity]
        class CmsGroup
        {
            /** @var Collection<int, CmsUser> */
            #[ManyToMany(targetEntity: CmsUser::class, mappedBy: 'groups', fetch: 'EXTRA_LAZY')]
            public Collection $users;
        }

    .. code-block:: annotation

        <?php
        namespace Doctrine\Tests\Models\CMS;

        /**
         * @Entity
         */
        class CmsGroup
        {
            /**
             * @ManyToMany(targetEntity="CmsUser", mappedBy="groups", fetch="EXTRA_LAZY")
             * @var Collection<int, CmsUser>
             */
            public Collection $users;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mapping xmlns="https://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="https://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="https://doctrine-project.org/schemas/orm/doctrine-mapping
                                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

            <entity name="Doctrine\Tests\Models\CMS\CmsGroup">
                <!-- ... -->
                <many-to-many field="users" target-entity="CmsUser" mapped-by="groups" fetch="EXTRA_LAZY" />
            </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        Doctrine\Tests\Models\CMS\CmsGroup:
          type: entity
          # ...
          manyToMany:
            users:
              targetEntity: CmsUser
              mappedBy: groups
              fetch: EXTRA_LAZY
