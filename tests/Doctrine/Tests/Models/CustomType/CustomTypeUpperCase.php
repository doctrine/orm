<?php

namespace Doctrine\Tests\Models\CustomType;

/**
 * @Entity
 * @Table(name="customtype_uppercases")
 */
class CustomTypeUpperCase
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="upper_case_string")
     */
    public $lowerCaseString;
}
