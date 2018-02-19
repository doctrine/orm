<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Quote\User;
use Doctrine\Tests\Models\Quote\UserData;

/**
 * Tests that association with quoted JoinColumn is updated
 */
class QuotedIdentifierAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{

	protected function setUp()
	{
		$this->useModelSet('quote');
		parent::setUp();
	}

	public function testUpdateEntityWithIdentifierAssociationWithQuotedJoinColumn()
	{
		$user = new User();
		$user->name = 'John Doe';
		$this->_em->persist($user);
		$this->_em->flush();

		$userData = new UserData();
		$userData->name = '123456789';
		$userData->user = $user;
		$this->_em->persist($userData);
		$this->_em->flush();

		$userData->name = '4321';
		$this->_em->flush();

		$queries = $this->_sqlLoggerStack->queries;
		$platform = $this->_em->getConnection()->getDatabasePlatform();
		$quotedTableName = $platform->quoteIdentifier('quote-user-data');
		$quotedColumn = $platform->quoteIdentifier('name');
		$quotedIdentifier = $platform->quoteIdentifier('user-id');

		$this->assertNotEquals('quote-user-data', $quotedTableName);
		$this->assertNotEquals('name', $quotedColumn);
		$this->assertNotEquals('user-id', $quotedIdentifier);

		$this->assertSQLEquals(sprintf('UPDATE %s SET %s = ? WHERE %s = ?', $quotedTableName, $quotedColumn, $quotedIdentifier), $queries[$this->_sqlLoggerStack->currentQuery - 1]['sql']);
	}

}
