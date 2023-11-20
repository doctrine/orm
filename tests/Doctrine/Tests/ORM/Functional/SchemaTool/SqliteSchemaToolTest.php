<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\Models\NonPublicSchemaJoins\User;
use Doctrine\Tests\OrmFunctionalTestCase;

class SqliteSchemaToolTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->_em->getConnection()->getDatabasePlatform() instanceof SQLitePlatform) {
            self::markTestSkipped('The ' . self::class . ' requires the use of sqlite.');
        }
    }

    public function testGetCreateSchemaSql(): void
    {
        $classes = [
            $this->_em->getClassMetadata(User::class),
        ];

        $tool = new SchemaTool($this->_em);
        $sql  = $tool->getCreateSchemaSql($classes);

        self::assertEquals('CREATE TABLE readers__user (id INTEGER NOT NULL, PRIMARY KEY(id))', $sql[0]);
        self::assertEquals('CREATE TABLE readers__author_reader (author_id INTEGER NOT NULL, reader_id INTEGER NOT NULL, PRIMARY KEY(author_id, reader_id), CONSTRAINT FK_83C36113F675F31B FOREIGN KEY (author_id) REFERENCES readers__user (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_83C361131717D737 FOREIGN KEY (reader_id) REFERENCES readers__user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)', $sql[1]);
        self::assertEquals('CREATE INDEX IDX_83C36113F675F31B ON readers__author_reader (author_id)', $sql[2]);
        self::assertEquals('CREATE INDEX IDX_83C361131717D737 ON readers__author_reader (reader_id)', $sql[3]);

        self::assertCount(4, $sql);
    }
}
