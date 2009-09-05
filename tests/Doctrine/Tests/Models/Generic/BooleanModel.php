<?php

namespace Doctrine\Tests\Models\Generic;

/**
 * @Entity
 * @Table(name="boolean_model")
 */
class BooleanModel
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
    /**
     * @Column(type="boolean")
     */
    public $booleanField;
}