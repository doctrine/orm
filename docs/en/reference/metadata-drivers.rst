Metadata Drivers
================

The heart of an object relational mapper is the mapping information
that glues everything together. It instructs the EntityManager how
it should behave when dealing with the different entities.

Core Metadata Drivers
---------------------

Doctrine provides a few different ways for you to specify your
metadata:


-  **XML files** (XmlDriver)
-  **Attributes** (AttributeDriver)
-  **PHP Code in files or static functions** (PhpDriver)

Something important to note about the above drivers is they are all
an intermediate step to the same end result. The mapping
information is populated to ``Doctrine\ORM\Mapping\ClassMetadata``
instances. So in the end, Doctrine only ever has to work with the
API of the ``ClassMetadata`` class to get mapping information for
an entity.

.. note::

    The populated ``ClassMetadata`` instances are also cached
    so in a production environment the parsing and populating only ever
    happens once. You can configure the metadata cache implementation
    using the ``setMetadataCacheImpl()`` method on the
    ``Doctrine\ORM\Configuration`` class:

    .. code-block:: php

        <?php
        $em->getConfiguration()->setMetadataCacheImpl(new ApcuCache());


All the drivers are in the ``Doctrine\ORM\Mapping\Driver`` namespace:

.. code-block:: php

    <?php
    $driver = new \Doctrine\ORM\Mapping\Driver\XmlDriver('/path/to/mapping/files');
    $em->getConfiguration()->setMetadataDriverImpl($driver);

Implementing Metadata Drivers
-----------------------------

In addition to the included metadata drivers you can very easily
implement your own. All you need to do is define a class which
implements the ``MappingDriver`` interface:

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace Doctrine\Persistence\Mapping\Driver;

    use Doctrine\Persistence\Mapping\ClassMetadata;

    /**
     * Contract for metadata drivers.
     */
    interface MappingDriver
    {
        /**
         * Loads the metadata for the specified class into the provided container.
         *
         * @param class-string<T> $className
         * @param ClassMetadata<T> $metadata
         *
         * @return void
         *
         * @template T of object
         */
        public function loadMetadataForClass(string $className, ClassMetadata $metadata);

        /**
         * Gets the names of all mapped classes known to this driver.
         *
         * @return list<class-string> The names of all mapped classes known to this driver.
         */
        public function getAllClassNames();

        /**
         * Returns whether the class with the specified name should have its metadata loaded.
         * This is only the case if it is either mapped as an Entity or a MappedSuperclass.
         *
         * @param class-string $className
         *
         * @return bool
         */
        public function isTransient(string $className);
    }

If you want to write a metadata driver to parse information from
some file format we've made your life a little easier by providing
the ``FileDriver`` implementation for you to extend from:

.. code-block:: php

    <?php

    use Doctrine\Persistence\Mapping\ClassMetadata;
    use Doctrine\Persistence\Mapping\Driver\FileDriver;

    class MyMetadataDriver extends FileDriver
    {
        /**
         * {@inheritDoc}
         */
        protected $_fileExtension = '.dcm.ext';

        /**
         * {@inheritDoc}
         */
        public function loadMetadataForClass($className, ClassMetadata $metadata)
        {
            $data = $this->_loadMappingFile($file);

            // populate ClassMetadata instance from $data
        }

        /**
         * {@inheritDoc}
         */
        protected function _loadMappingFile($file)
        {
            // parse contents of $file and return php data structure
        }
    }

.. note::

    When using the ``FileDriver`` it requires that you only have one
    entity defined per file and the file named after the class described
    inside where namespace separators are replaced by periods. So if you
    have an entity named ``Entities\User`` and you wanted to write a
    mapping file for your driver above you would need to name the file
    ``Entities.User.dcm.ext`` for it to be recognized.


Now you can use your ``MyMetadataDriver`` implementation by setting
it with the ``setMetadataDriverImpl()`` method:

.. code-block:: php

    <?php
    $driver = new MyMetadataDriver('/path/to/mapping/files');
    $em->getConfiguration()->setMetadataDriverImpl($driver);

ClassMetadata
-------------

The last piece you need to know and understand about metadata in
Doctrine ORM is the API of the ``ClassMetadata`` classes. You need to
be familiar with them in order to implement your own drivers but
more importantly to retrieve mapping information for a certain
entity when needed.

You have all the methods you need to manually specify the mapping
information instead of using some mapping file to populate it from.

You can read more about the API of the ``ClassMetadata`` classes in
the PHP Mapping chapter.

Getting ClassMetadata Instances
-------------------------------

If you want to get the ``ClassMetadata`` instance for an entity in
your project to programmatically use some mapping information to
generate some HTML or something similar you can retrieve it through
the ``ClassMetadataFactory``:

.. code-block:: php

    <?php
    $cmf = $em->getMetadataFactory();
    $class = $cmf->getMetadataFor('MyEntityName');

Now you can learn about the entity and use the data stored in the
``ClassMetadata`` instance to get all mapped fields for example and
iterate over them:

.. code-block:: php

    <?php
    foreach ($class->fieldMappings as $fieldMapping) {
        echo $fieldMapping['fieldName'] . "\n";
    }
