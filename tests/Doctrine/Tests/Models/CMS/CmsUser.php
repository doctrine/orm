<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="cms_users")
 */
class CmsUser
{
    /**
     * @ORM\Id @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
    /** @ORM\Column(type="string", length=50, nullable=true) */
    public $status;
    /** @ORM\Column(type="string", length=255, unique=true) */
    public $username;
    /** @ORM\Column(type="string", length=255) */
    public $name;
    /**
     * @ORM\OneToMany(
     *     targetEntity=CmsPhonenumber::class,
     *     mappedBy="user",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    public $phonenumbers;
    /**
     * @ORM\OneToMany(
     *     targetEntity=CmsArticle::class,
     *     mappedBy="user"
     * )
     */
    public $articles;
    /**
     * @ORM\OneToOne(
     *     targetEntity=CmsAddress::class,
     *     mappedBy="user",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     */
    public $address;
    /**
     * @ORM\OneToOne(
     *     targetEntity=CmsEmail::class,
     *     inversedBy="user",
     *     cascade={"persist"},
     *     orphanRemoval=true
     * )
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true)
     */
    public $email;
    /**
     * @ORM\ManyToMany(targetEntity=CmsGroup::class, inversedBy="users", cascade={"persist"})
     * @ORM\JoinTable(name="cms_users_groups",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     *      )
     */
    public $groups;
    /**
     * @ORM\ManyToMany(targetEntity=CmsTag::class, inversedBy="users", cascade={"all"})
     * @ORM\JoinTable(name="cms_users_tags",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id")}
     *      )
     */
    public $tags;

    public $nonPersistedProperty;

    public $nonPersistedPropertyObject;

    public function __construct()
    {
        $this->phonenumbers = new ArrayCollection();
        $this->articles     = new ArrayCollection();
        $this->groups       = new ArrayCollection();
        $this->tags         = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * Adds a phonenumber to the user.
     */
    public function addPhonenumber(CmsPhonenumber $phone)
    {
        $this->phonenumbers[] = $phone;
        $phone->setUser($this);
    }

    public function getPhonenumbers()
    {
        return $this->phonenumbers;
    }

    public function addArticle(CmsArticle $article)
    {
        $this->articles[] = $article;
        $article->setAuthor($this);
    }

    public function addGroup(CmsGroup $group)
    {
        $this->groups[] = $group;
        $group->addUser($this);
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function addTag(CmsTag $tag)
    {
        $this->tags[] = $tag;
        $tag->addUser($this);
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function removePhonenumber($index)
    {
        if (isset($this->phonenumbers[$index])) {
            $ph = $this->phonenumbers[$index];
            unset($this->phonenumbers[$index]);
            $ph->user = null;
            return true;
        }
        return false;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function setAddress(CmsAddress $address)
    {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }

    /**
     * @return CmsEmail
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(?CmsEmail $email = null)
    {
        if ($this->email !== $email) {
            $this->email = $email;

            if ($email) {
                $email->setUser($this);
            }
        }
    }
}
