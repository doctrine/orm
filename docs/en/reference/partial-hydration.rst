Partial Hydration
=================

Partial hydration of entities is allowed in the array hydrator, when
only a subset of the fields of an entity are loaded from the database
and the nested results are still created based on the entity relationship structure.

.. code-block:: php

    <?php
    $users = $em->createQuery("SELECT PARTIAL u.{id,name}, partial a.{id,street} FROM MyApp\Domain\User u JOIN u.addresses a")
                ->getArrayResult();

This is a useful optimization when you are not interested in all fields of an entity
for performance reasons, for example in use-cases for exporting or rendering lots of data.
