Advanced field value conversion using custom mapping types
==========================================================

.. sectionauthor:: Jan Sorgalla <jsorgalla@googlemail.com>

When creating entities, you sometimes have the need to transform field values
before they are saved to the database. In Doctrine you can use Custom Mapping 
Types to solve this (see: :ref:`reference-basic-mapping-custom-mapping-types`).

There are several ways to achieve this: converting the value inside the Type
class, converting the value on the database-level or a combination of both.

This article describes the third way by implementing the MySQL specific column
type `Point <http://dev.mysql.com/doc/refman/5.5/en/gis-class-point.html>`_.

The `Point` type is part of the `Spatial extension <http://dev.mysql.com/doc/refman/5.5/en/spatial-extensions.html>`_
of MySQL and enables you to store a single location in a coordinate space by
using x and y coordinates.

As you might have already guessed, you can use the Point type to store a 
longitude/latitude pair to represent a geographic location.

The entity
----------

We create a simple entity which contains a field ``$point`` which should hold
a value object ``Point`` representing the latitude and longitude of the position.

The entity class:

.. code-block:: php

    <?php
    
    namespace Geo;
 
    /**
     * @Entity
     */
    class Location
    {
        /**
         * @Column(type="point")
         *
         * @var \Geo\Point
         */
        private $point;

        /**
         * @param \Geo\Point $point
         */
        public function setPoint(\Geo\Point $point)
        {
            $this->point = $point;
        }

        /**
         * @return \Geo\Point
         */
        public function getPoint()
        {
            return $this->point;
        }
    }

The point class:

.. code-block:: php

    <?php
    
    namespace Geo;

    class Point
    {
        public function __construct($latitude, $longitude)
        {
            $this->latitude  = $latitude;
            $this->longitude = $longitude;
        }

        public function getLatitude()
        {
            return $this->latitude;
        }

        public function getLongitude()
        {
            return $this->longitude;
        }
    }
