<?php

declare(strict_types=1);

/**
 * @Entity(repositoryClass="DDC3231User1NoNamespaceRepository")
 * @Table(name="no_namespace_users")
 */
class DDC3231User1NoNamespace
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @Column(type="string", length=255)
     */
    protected $name;
}
