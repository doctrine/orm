<?php

namespace Doctrine\Tests\Models\CustomType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="customtype_uppercases")
 */
class CustomTypeUpperCase
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="upper_case_string")
     */
    public $lowerCaseString;

    /**
     * @ORM\Column(type="upper_case_string", name="named_lower_case_string", nullable = true)
     */
    public $namedLowerCaseString;
}
