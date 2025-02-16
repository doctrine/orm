<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\TypedProperties;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\CMS\CmsEmail;

#[ORM\Entity]
#[ORM\Table(name: 'cms_users_typed')]
class UserTyped
{
    #[ORM\Id]
    #[ORM\Column]
    #[ORM\GeneratedValue]
    public int $id;

    #[ORM\Column(length: 50)]
    public string|null $status = null;

    #[ORM\Column(length: 255, unique: true)]
    public string $username;

    #[ORM\Column]
    public DateInterval $dateInterval;

    #[ORM\Column]
    public DateTime $dateTime;

    #[ORM\Column]
    public DateTimeImmutable $dateTimeImmutable;

    #[ORM\Column]
    public array $array;

    #[ORM\Column]
    public bool $boolean;

    #[ORM\Column]
    public float $float;

    #[ORM\OneToOne(cascade: ['persist'], orphanRemoval: true)]
    #[ORM\JoinColumn]
    public CmsEmail $email;

    #[ORM\ManyToOne]
    public CmsEmail|null $mainEmail = null;

    #[ORM\Embedded]
    public Contact|null $contact = null;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
        $metadata->setPrimaryTable(
            ['name' => 'cms_users_typed'],
        );

        $metadata->mapField(
            [
                'id' => true,
                'fieldName' => 'id',
            ],
        );
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        $metadata->mapField(
            [
                'fieldName' => 'status',
                'length' => 50,
            ],
        );
        $metadata->mapField(
            [
                'fieldName' => 'username',
                'length' => 255,
                'unique' => true,
            ],
        );
        $metadata->mapField(
            ['fieldName' => 'dateInterval'],
        );
        $metadata->mapField(
            ['fieldName' => 'dateTime'],
        );
        $metadata->mapField(
            ['fieldName' => 'dateTimeImmutable'],
        );
        $metadata->mapField(
            ['fieldName' => 'array'],
        );
        $metadata->mapField(
            ['fieldName' => 'boolean'],
        );
        $metadata->mapField(
            ['fieldName' => 'float'],
        );

        $metadata->mapOneToOne(
            [
                'fieldName' => 'email',
                'cascade' =>
                    [0 => 'persist'],
                'joinColumns' =>
                    [
                        0 =>
                            ['referencedColumnName' => 'id'],
                    ],
                'orphanRemoval' => true,
            ],
        );

        $metadata->mapManyToOne(
            ['fieldName' => 'mainEmail'],
        );

        $metadata->mapEmbedded(['fieldName' => 'contact']);
    }
}
