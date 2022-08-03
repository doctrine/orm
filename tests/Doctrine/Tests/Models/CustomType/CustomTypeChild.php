<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CustomType;

/**
 * @Entity
 * @Table(name="customtype_children")
 */
class CustomTypeChild
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(type="upper_case_string")
     */
    public $lowerCaseString = 'foo';
}
