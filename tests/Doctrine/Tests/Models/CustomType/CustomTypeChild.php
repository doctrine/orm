<?php

namespace Doctrine\Tests\Models\CustomType;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="customtype_children")
 */
class CustomTypeChild
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="upper_case_string")
     */
    public $lowerCaseString = 'foo';
}
