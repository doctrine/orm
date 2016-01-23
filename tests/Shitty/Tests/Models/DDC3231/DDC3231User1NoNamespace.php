<?php

/**
 * @Entity(repositoryClass="DDC3231User1NoNamespaceRepository")
 * @Table(name="no_namespace_users")
 */
class DDC3231User1NoNamespace
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(type="string", length=255)
     */
    protected $name;

}
