<?php

namespace Doctrine\Tests\Models\DDC3346;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ddc3346_articles")
 */
class DDC3346Article
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var DDC3346Author
     *
     * @ORM\ManyToOne(targetEntity="DDC3346Author", inversedBy="articles")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;
}
