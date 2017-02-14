<?php

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity(repositoryClass="DDC3231User1NoNamespaceRepository")
 * @ORM\Table(name="no_namespace_users")
 */
class DDC3231User1NoNamespace
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

}
