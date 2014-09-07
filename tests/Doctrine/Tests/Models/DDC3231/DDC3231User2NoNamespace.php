<?php

/**
 * @Entity(repositoryClass="DDC3231User2NoNamespaceRepository")
 * @Table(name="no_namespace_users2")
 */
class DDC3231User2NoNamespace
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
