<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;

#[ORM\Entity]
#[ORM\Table(name: 'cms_addresses')]
#[ORM\EntityListeners(['CmsAddressListener'])]
class CmsAddress
{
    /** @var int */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id;

    /** @var string */
    #[Column(length: 50)]
    public $country;

    /** @var string */
    #[Column(length: 50)]
    public $zip;

    /** @var string */
    #[Column(length: 50)]
    public $city;

    /**
     * Testfield for Schema Updating Tests.
     *
     * @var string
     */
    public $street;

    /** @var CmsUser */
    #[OneToOne(targetEntity: 'CmsUser', inversedBy: 'address')]
    #[JoinColumn(referencedColumnName: 'id')]
    public $user;

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): CmsUser
    {
        return $this->user;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getZipCode(): string
    {
        return $this->zip;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setUser(CmsUser $user): void
    {
        if ($this->user !== $user) {
            $this->user = $user;
            $user->setAddress($this);
        }
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setPrimaryTable(
            ['name' => 'company_person'],
        );

        $metadata->mapField(
            [
                'id'        => true,
                'fieldName' => 'id',
                'type'      => 'integer',
            ],
        );

        $metadata->mapField(
            [
                'fieldName' => 'zip',
                'length'    => 50,
            ],
        );

        $metadata->mapField(
            [
                'fieldName' => 'city',
                'length'    => 50,
            ],
        );

        $metadata->mapOneToOne(
            [
                'fieldName'     => 'user',
                'targetEntity'  => 'CmsUser',
                'joinColumns'   => [['referencedColumnName' => 'id']],
            ],
        );

        $metadata->addEntityListener(Events::postPersist, 'CmsAddressListener', 'postPersist');
        $metadata->addEntityListener(Events::prePersist, 'CmsAddressListener', 'prePersist');

        $metadata->addEntityListener(Events::postUpdate, 'CmsAddressListener', 'postUpdate');
        $metadata->addEntityListener(Events::preUpdate, 'CmsAddressListener', 'preUpdate');

        $metadata->addEntityListener(Events::postRemove, 'CmsAddressListener', 'postRemove');
        $metadata->addEntityListener(Events::preRemove, 'CmsAddressListener', 'preRemove');

        $metadata->addEntityListener(Events::preFlush, 'CmsAddressListener', 'preFlush');
        $metadata->addEntityListener(Events::postLoad, 'CmsAddressListener', 'postLoad');
    }
}
