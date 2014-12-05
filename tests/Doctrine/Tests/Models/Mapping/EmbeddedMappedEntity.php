<?php

namespace Doctrine\Tests\Models\Mapping;

/**
 * @Entity
 */
class EmbeddedMappedEntity
{
    /**
     * @Id
     * @Column(type="integer")
     */
    protected $id;
    
    /**
     * @Embedded(class = "Doctrine\Tests\Models\Mapping\EmbeddableObject")
     */
    protected $embedded;
}
