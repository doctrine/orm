<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InverseJoinColumn;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

#[Table(name: 'cms_users')]
#[Entity]
class CmsUser
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var string */
    #[Column(type: 'string', length: 50, nullable: true)]
    public $status;

    /** @var string */
    #[Column(type: 'string', length: 255, unique: true)]
    public $username;

    /** @phpstan-var string|null */
    #[Column(type: 'string', length: 255)]
    public $name;

    /** @phpstan-var Collection<int, CmsPhonenumber> */
    #[OneToMany(targetEntity: 'CmsPhonenumber', mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    public $phonenumbers;

    /** @phpstan-var Collection<int, CmsArticle> */
    #[OneToMany(targetEntity: 'CmsArticle', mappedBy: 'user', cascade: ['detach'])]
    public $articles;

    /** @var CmsAddress */
    #[OneToOne(targetEntity: 'CmsAddress', mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    public $address;

    /** @var CmsEmail */
    #[OneToOne(targetEntity: 'CmsEmail', inversedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
    #[JoinColumn(referencedColumnName: 'id', nullable: true)]
    public $email;

    /** @phpstan-var Collection<int, CmsGroup> */
    #[JoinTable(name: 'cms_users_groups')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'group_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: 'CmsGroup', inversedBy: 'users', cascade: ['persist', 'detach'])]
    public $groups;

    /** @var Collection<int, CmsTag> */
    #[JoinTable(name: 'cms_users_tags')]
    #[JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    #[ManyToMany(targetEntity: 'CmsTag', inversedBy: 'users', cascade: ['all'])]
    public $tags;

    /** @var mixed */
    public $nonPersistedProperty;

    /** @var mixed */
    public $nonPersistedPropertyObject;

    public function __construct()
    {
        $this->phonenumbers = new ArrayCollection();
        $this->articles     = new ArrayCollection();
        $this->groups       = new ArrayCollection();
        $this->tags         = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * Adds a phonenumber to the user.
     */
    public function addPhonenumber(CmsPhonenumber $phone): void
    {
        $this->phonenumbers[] = $phone;
        $phone->setUser($this);
    }

    /** @phpstan-return Collection<int, CmsPhonenumber> */
    public function getPhonenumbers(): Collection
    {
        return $this->phonenumbers;
    }

    public function addArticle(CmsArticle $article): void
    {
        $this->articles[] = $article;
        $article->setAuthor($this);
    }

    public function addGroup(CmsGroup $group): void
    {
        $this->groups[] = $group;
        $group->addUser($this);
    }

    /** @phpstan-return Collection<int, CmsGroup> */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addTag(CmsTag $tag): void
    {
        $this->tags[] = $tag;
        $tag->addUser($this);
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function removePhonenumber($index): bool
    {
        if (isset($this->phonenumbers[$index])) {
            $ph = $this->phonenumbers[$index];
            unset($this->phonenumbers[$index]);
            $ph->user = null;

            return true;
        }

        return false;
    }

    public function getAddress(): CmsAddress
    {
        return $this->address;
    }

    public function setAddress(CmsAddress $address): void
    {
        if ($this->address !== $address) {
            $this->address = $address;
            $address->setUser($this);
        }
    }

    public function getEmail(): CmsEmail|null
    {
        return $this->email;
    }

    public function setEmail(CmsEmail|null $email = null): void
    {
        if ($this->email !== $email) {
            $this->email = $email;

            if ($email) {
                $email->setUser($this);
            }
        }
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setPrimaryTable(
            ['name' => 'cms_users'],
        );
    }
}
