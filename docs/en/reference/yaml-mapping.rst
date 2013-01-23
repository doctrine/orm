YAML Mapping
============

The YAML mapping driver enables you to provide the ORM metadata in
form of YAML documents.

The YAML mapping document of a class is loaded on-demand the first
time it is requested and subsequently stored in the metadata cache.
In order to work, this requires certain conventions:


-  Each entity/mapped superclass must get its own dedicated YAML
   mapping document.
-  The name of the mapping document must consist of the fully
   qualified name of the class, where namespace separators are
   replaced by dots (.).
-  All mapping documents should get the extension ".dcm.yml" to
   identify it as a Doctrine mapping file. This is more of a
   convention and you are not forced to do this. You can change the
   file extension easily enough.

-

.. code-block:: php

    <?php
    $driver->setFileExtension('.yml');

It is recommended to put all YAML mapping documents in a single
folder but you can spread the documents over several folders if you
want to. In order to tell the YamlDriver where to look for your
mapping documents, supply an array of paths as the first argument
of the constructor, like this:

.. code-block:: php

    <?php
    use Doctrine\ORM\Mapping\Driver\YamlDriver;

    // $config instanceof Doctrine\ORM\Configuration
    $driver = new YamlDriver(array('/path/to/files'));
    $config->setMetadataDriverImpl($driver);

Simplified YAML Driver
~~~~~~~~~~~~~~~~~~~~~~

The Symfony project sponsored a driver that simplifies usage of the YAML Driver.
The changes between the original driver are:

1. File Extension is .orm.yml
2. Filenames are shortened, "MyProject\Entities\User" will become User.orm.yml
3. You can add a global file and add multiple entities in this file.

Configuration of this client works a little bit different:

.. code-block:: php

    <?php
    $namespaces = array(
        '/path/to/files1' => 'MyProject\Entities',
        '/path/to/files2' => 'OtherProject\Entities'
    );
    $driver = new \Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver($namespaces);
    $driver->setGlobalBasename('global'); // global.orm.yml

Example
-------

As a quick start, here is a small example document that makes use
of several common elements:

.. code-block:: yaml

    # Doctrine.Tests.ORM.Mapping.User.dcm.yml
    Doctrine\Tests\ORM\Mapping\User:
      type: entity
      table: cms_users
      indexes:
        name_index:
          columns: [ name ]
      id:
        id:
          type: integer
          generator:
            strategy: AUTO
      fields:
        name:
          type: string
          length: 50
      oneToOne:
        address:
          targetEntity: Address
          joinColumn:
            name: address_id
            referencedColumnName: id
      oneToMany:
        phonenumbers:
          targetEntity: Phonenumber
          mappedBy: user
          cascade: ["persist", "merge"]
      manyToMany:
        groups:
          targetEntity: Group
          joinTable:
            name: cms_users_groups
            joinColumns:
              user_id:
                referencedColumnName: id
            inverseJoinColumns:
              group_id:
                referencedColumnName: id
      lifecycleCallbacks:
        prePersist: [ doStuffOnPrePersist, doOtherStuffOnPrePersistToo ]
        postPersist: [ doStuffOnPostPersist ]

Be aware that class-names specified in the YAML files should be
fully qualified.


