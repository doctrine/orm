<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Doctrine\Tests\OrmTestCase;

class GH10473Test extends OrmTestCase
{
    public function testMappedSuperclassAssociationsCanBeResolvedToEntities(): void
    {
        $em = $this->getTestEntityManager();

        $resolveTargetEntity = new ResolveTargetEntityListener();

        $resolveTargetEntity->addResolveTargetEntity(
            GH10473BaseUser::class,
            GH10473UserImplementation::class,
            [],
        );

        $em->getEventManager()->addEventSubscriber($resolveTargetEntity);

        $userMetadata = $em->getClassMetadata(GH10473UserImplementation::class);

        self::assertFalse($userMetadata->isMappedSuperclass);
        self::assertTrue($userMetadata->isInheritanceTypeNone());

        $socialMediaAccountsMapping = $userMetadata->getAssociationMapping('socialMediaAccounts');
        self::assertArrayNotHasKey('inherited', $socialMediaAccountsMapping);
        self::assertTrue((bool) ($socialMediaAccountsMapping['type'] & ClassMetadata::TO_MANY));
        self::assertFalse($socialMediaAccountsMapping['isOwningSide']);
        self::assertSame(GH10473SocialMediaAccount::class, $socialMediaAccountsMapping['targetEntity']);
        self::assertSame('user', $socialMediaAccountsMapping['mappedBy']);

        $createdByMapping = $userMetadata->getAssociationMapping('createdBy');
        self::assertArrayNotHasKey('inherited', $createdByMapping);
        self::assertTrue((bool) ($createdByMapping['type'] & ClassMetadata::TO_ONE));
        self::assertTrue($createdByMapping['isOwningSide']);
        self::assertSame(GH10473UserImplementation::class, $createdByMapping['targetEntity']);
        self::assertSame('createdUsers', $createdByMapping['inversedBy']);

        $createdUsersMapping = $userMetadata->getAssociationMapping('createdUsers');
        self::assertArrayNotHasKey('inherited', $createdUsersMapping);
        self::assertTrue((bool) ($createdUsersMapping['type'] & ClassMetadata::TO_MANY));
        self::assertFalse($createdUsersMapping['isOwningSide']);
        self::assertSame(GH10473UserImplementation::class, $createdUsersMapping['targetEntity']);
        self::assertSame('createdBy', $createdUsersMapping['mappedBy']);

        $socialMediaAccountMetadata = $em->getClassMetadata(GH10473SocialMediaAccount::class);

        self::assertFalse($socialMediaAccountMetadata->isMappedSuperclass);
        self::assertTrue($socialMediaAccountMetadata->isInheritanceTypeNone());

        $userMapping = $socialMediaAccountMetadata->getAssociationMapping('user');
        self::assertArrayNotHasKey('inherited', $userMapping);
        self::assertTrue((bool) ($userMapping['type'] & ClassMetadata::TO_ONE));
        self::assertTrue($userMapping['isOwningSide']);
        self::assertSame(GH10473UserImplementation::class, $userMapping['targetEntity']);
        self::assertSame('socialMediaAccounts', $userMapping['inversedBy']);
    }
}

#[ORM\MappedSuperclass]
abstract class GH10473BaseUser
{
    /** @var int */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    private $id;

    /** @var Collection */
    #[ORM\OneToMany(targetEntity: GH10473SocialMediaAccount::class, mappedBy: 'user')]
    private $socialMediaAccounts;

    /** @var GH10473BaseUser */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'createdUsers')]
    private $createdBy;

    /** @var Collection */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'createdBy')]
    private $createdUsers;
}

#[ORM\Entity]
class GH10473SocialMediaAccount
{
    /** @var int */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    private $id;

    /** @var GH10473BaseUser */
    #[ORM\ManyToOne(targetEntity: GH10473BaseUser::class, inversedBy: 'socialMediaAccounts')]
    private $user;
}

#[ORM\Entity]
class GH10473UserImplementation extends GH10473BaseUser
{
}
