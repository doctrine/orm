<?php

namespace Doctrine\Tests\Models\DDC3346;

/**
 * @Entity
 * @Table(name="ddc3346_articles")
 */
class DDC3346Article
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var DDC3346Author
     *
     * @ManyToOne(targetEntity="DDC3346Author", inversedBy="articles")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;
}
