Change Tracking Policies
========================

Change tracking is the process of determining what has changed in
managed entities since the last time they were synchronized with
the database.

Doctrine provides 2 different change tracking policies, each having
its particular advantages and disadvantages. The change tracking
policy can be defined on a per-class basis (or more precisely,
per-hierarchy).

Deferred Implicit
~~~~~~~~~~~~~~~~~

The deferred implicit policy is the default change tracking policy
and the most convenient one. With this policy, Doctrine detects the
changes by a property-by-property comparison at commit time and
also detects changes to entities or new entities that are
referenced by other managed entities ("persistence by
reachability"). Although the most convenient policy, it can have
negative effects on performance if you are dealing with large units
of work (see "Understanding the Unit of Work"). Since Doctrine
can't know what has changed, it needs to check all managed entities
for changes every time you invoke EntityManager#flush(), making
this operation rather costly.

Deferred Explicit
~~~~~~~~~~~~~~~~~

The deferred explicit policy is similar to the deferred implicit
policy in that it detects changes through a property-by-property
comparison at commit time. The difference is that Doctrine ORM only
considers entities that have been explicitly marked for change detection
through a call to EntityManager#persist(entity) or through a save
cascade. All other entities are skipped. This policy therefore
gives improved performance for larger units of work while
sacrificing the behavior of "automatic dirty checking".

Therefore, flush() operations are potentially cheaper with this
policy. The negative aspect this has is that if you have a rather
large application and you pass your objects through several layers
for processing purposes and business tasks you may need to track
yourself which entities have changed on the way so you can pass
them to EntityManager#persist().

This policy can be configured as follows:

.. code-block:: php

    <?php

    #[Entity]
    #[ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
    class User
    {
        // ...
    }
