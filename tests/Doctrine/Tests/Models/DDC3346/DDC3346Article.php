<?php

declare(strict_types=1);

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
     * @ORM\ManyToOne(targetEntity=DDC3346Author::class, inversedBy="articles")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *
     * @var DDC3346Author
     */
    public $user;
}
