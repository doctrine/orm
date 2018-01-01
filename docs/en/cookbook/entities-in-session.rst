Entities in the Session
=======================

There are several use-cases to save entities in the session, for example:

1.  User object
2.  Multi-step forms

To achieve this with Doctrine you have to pay attention to some details to get
this working.

Merging entity into an EntityManager
------------------------------------

In Doctrine, an entity objects has to be "managed" by an EntityManager to be
updated. Entities saved into the session are not managed in the next request
anymore. This means that you have to register these entities with an
EntityManager again if you want to change them or use them as part of
references between other entities.

It is a good idea to avoid storing entities in serialized formats such as
``$_SESSION``: instead, store the entity identifiers or raw data.

For a representative User object the code to get turn an instance from
the session into a managed Doctrine object looks like this:

.. code-block:: php

    <?php
    require_once 'bootstrap.php';
    $em = GetEntityManager(); // creates an EntityManager

    session_start();
    if (isset($_SESSION['user'])) {
        $user = $em->find(User::class, $_SESSION['user']);

        if (! $user instanceof User) {
            // user not found in the database
            $_SESSION['user'] = null;
        }
    }

Serializing entities into the session
-------------------------------------

Serializing entities in the session means serializing also all associated
entities and collections. While this might look like a quick solution in
simple applications, you will encounter problems due to the fact that the
data in the session is stale.

In order to prevent working with stale data, try saving only minimal
information about your entities in your session, without storing entire
entity objects. Should you need the full information of an object, so it
is suggested to re-query the database, which is usually the most
authoritative source of information in typical PHP applications.
