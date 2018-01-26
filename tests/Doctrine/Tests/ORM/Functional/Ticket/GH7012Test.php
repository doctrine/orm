<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\Models\Quote\User as QuotedUser;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7012Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        $this->useModelSet('quote');

        parent::setUp();

        $this->setUpEntitySchema([GH7012UserData::class]);
    }

    /**
     * @group 7012
     */
    public function testUpdateEntityWithIdentifierAssociationWithQuotedJoinColumn() : void
    {
        $user       = new QuotedUser();
        $user->name = 'John Doe';

        $this->em->persist($user);
        $this->em->flush();

        $userData = new GH7012UserData($user, '123456789');

        $this->em->persist($userData);
        $this->em->flush();

        $userData->name = '4321';
        $this->em->flush();

        $platform         = $this->em->getConnection()->getDatabasePlatform();
        $quotedTableName  = $platform->quoteIdentifier('quote-user-data');
        $quotedColumn     = $platform->quoteIdentifier('name');
        $quotedIdentifier = $platform->quoteIdentifier('user-id');

        self::assertNotEquals('quote-user-data', $quotedTableName);
        self::assertNotEquals('name', $quotedColumn);
        self::assertNotEquals('user-id', $quotedIdentifier);

        $queries = $this->sqlLoggerStack->queries;

        self::assertSQLEquals(
            sprintf('UPDATE %s SET %s = ? WHERE %s = ?', $quotedTableName, $quotedColumn, $quotedIdentifier),
            $queries[$this->sqlLoggerStack->currentQuery - 1]['sql']
        );
    }
}


/**
 * @ORM\Entity
 * @ORM\Table(name="quote-user-data")
 */
class GH7012UserData
{
    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity=Doctrine\Tests\Models\Quote\User::class)
     * @ORM\JoinColumn(name="user-id", referencedColumnName="user-id", onDelete="CASCADE")
     */
    public $user;

    /**
     * @ORM\Column(type="string", name="name")
     */
    public $name;

    public function __construct(QuotedUser $user, string $name)
    {
        $this->user = $user;
        $this->name = $name;
    }
}
