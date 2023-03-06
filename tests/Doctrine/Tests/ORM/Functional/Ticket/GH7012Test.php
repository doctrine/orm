<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\Models\Quote\User as QuotedUser;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

final class GH7012Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('quote');

        parent::setUp();

        $this->setUpEntitySchema([GH7012UserData::class]);
    }

    #[Group('GH-7012')]
    public function testUpdateEntityWithIdentifierAssociationWithQuotedJoinColumn(): void
    {
        $user       = new QuotedUser();
        $user->name = 'John Doe';

        $this->_em->persist($user);
        $this->_em->flush();

        $userData = new GH7012UserData($user, '123456789');

        $this->_em->persist($userData);
        $this->_em->flush();

        $userData->name = '4321';
        $this->_em->flush();

        self::assertSame(
            '4321',
            $this->_em->getRepository(GH7012UserData::class)->findOneBy(['user' => $user])->name,
        );
    }
}


#[Table(name: '`quote-user-data`')]
#[Entity]
class GH7012UserData
{
    public function __construct(
        #[Id]
        #[OneToOne(targetEntity: QuotedUser::class)]
        #[JoinColumn(name: '`user-id`', referencedColumnName: '`user-id`', onDelete: 'CASCADE')]
        public QuotedUser $user,
        #[Column(type: 'string', name: '`name`')]
        public string $name,
    ) {
    }
}
