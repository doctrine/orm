<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CustomType;

/**
 * @Entity
 * @Table(name="customtype_uppercases")
 */
class CustomTypeUpperCase
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
    public $lowerCaseString;

    /**
     * @var string
     * @Column(type="upper_case_string", name="named_lower_case_string", nullable = true)
     */
    public $namedLowerCaseString;
}
