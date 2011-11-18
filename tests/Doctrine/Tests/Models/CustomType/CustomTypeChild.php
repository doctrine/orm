<?php

namespace Doctrine\Tests\Models\CustomType;

/**
 * @Entity
 * @Table(name="customtype_children")
 */
class CustomTypeChild
{
    /**
     * @Id @Column(type="negative_to_positive")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="upper_case_string")
     */
    public $lowerCaseString = 'foo';
}
