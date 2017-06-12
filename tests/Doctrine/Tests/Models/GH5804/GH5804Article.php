<?php

namespace Doctrine\Tests\Models\GH5804;

/**
 * @Entity
 * @Table(name="gh5804_articles")
 */
class GH5804Article
{
    /**
     * @Id
     * @Column(type="GH5804Type")
     * @GeneratedValue(strategy="CUSTOM")
     * @CustomIdGenerator(class="\Doctrine\Tests\ORM\Functional\Ticket\GH5804Generator")
     */
    public $id;
    /**
     * @Version @column(type="integer")
     */
    public $version;

    /**
     * @Column(type="text")
     */
    public $text;


}