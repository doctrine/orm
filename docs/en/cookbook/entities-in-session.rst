Entities in the Session
=======================

There are several use-cases to save entities in the session, for example:

1.  User data
2.  Multi-step forms

To achieve this with Doctrine you have to pay attention to some details to get
this working.

Updating an entity
------------------

In Doctrine an entity objects has to be "managed" by an EntityManager to be
updatable. Entities saved into the session are not managed in the next request
anymore. This means that you have to update the entities with the stored session
data after you fetch the entities from the EntityManager again.

For a representative User object the code to get data from the session into a
managed Doctrine object can look like these examples:

Working with scalars
~~~~~~~~~~~~~~~~~~~~

In simpler applications there is no need to work with objects in sessions and you can use
separate session elements.

.. code-block:: php

    <?php
    require_once 'bootstrap.php';

    session_start();
    if (isset($_SESSION['userId']) && is_int($_SESSION['userId'])) {
        $userId = $_SESSION['userId'];

        $em = GetEntityManager(); // creates an EntityManager
        $user = $em->find(User::class, $userId);

        $user->setValue($_SESSION['storedValue']);

        $em->flush();
    }

Working with custom data transfer objects
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If objects are needed, we discourage the storage of entity objects in the session. It's
preferable to use a `DTO (data transfer object) <https://en.wikipedia.org/wiki/Data_transfer_object>`_
instead and merge the DTO data later with the entity.

.. code-block:: php

    <?php
    require_once 'bootstrap.php';

    session_start();
    if (isset($_SESSION['user']) && $_SESSION['user'] instanceof UserDto) {
        $userDto = $_SESSION['user'];

        $em = GetEntityManager(); // creates an EntityManager
        $userEntity = $em->find(User::class, $userDto->getId());

        $userEntity->populateFromDto($userDto);

        $em->flush();
    }

Serializing entity into the session
-----------------------------------

Entities that are serialized into the session normally contain references to
other entities as well. Think of the user entity has a reference to their
articles, groups, photos or many other different entities. If you serialize
this object into the session then you don't want to serialize the related
entities as well. This is why you shouldn't serialize an entity and use
only the needed values of it. This can happen with the help of a DTO.

.. code-block:: php

    <?php
    require_once 'bootstrap.php';

    $em = GetEntityManager(); // creates an EntityManager 

    $user = $em->find("User", 1);
    $userDto = new UserDto($user->getId(), $user->getFirstName(), $user->getLastName());
    // or "UserDto::createFrom($user);", but don't store an entity in a property. Only its values without relations.

    $_SESSION['user'] = $userDto;


